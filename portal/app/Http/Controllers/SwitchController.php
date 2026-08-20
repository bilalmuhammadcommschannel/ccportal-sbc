<?php

namespace App\Http\Controllers;

use App\Models\Ov500\Carrier;
use App\Models\Ov500\CustomerBalance;
use App\Models\Ov500\EndpointHeader;
use App\Models\Ov500\SipAccount;
use App\Services\RatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FreeSWITCH-facing call control. NOT a browser surface — no session, no CSRF,
 * loopback + shared-secret only (SwitchAuth). Every query is parameterised
 * (Eloquent / bound), the deliberate inverse of OV500's api/lib/OVS.php which
 * sprintf'd SIP-derived fields straight into SQL.
 *
 *   POST /switch/dialplan  -> mod_xml_curl routing decision (FreeSWITCH XML)
 *   POST /switch/event     -> ring/answer/hangup call-state update
 *   POST /switch/cdr       -> rate the completed call + debit balance
 */
class SwitchController extends Controller
{
    /**
     * mod_xml_curl dialplan binding. FreeSWITCH sends call variables; we decide
     * how to route and return a FreeSWITCH dialplan document.
     */
    public function dialplan(Request $request)
    {
        // The portal only owns the customer-origination context ('default'). For any
        // other context (e.g. the carrier/termination leg) return not-found so
        // FreeSWITCH falls back to its static dialplan.
        $ctx = (string) $request->input('Hunt-Context', $request->input('Caller-Context', $request->input('context', '')));
        if ($ctx !== '' && $ctx !== 'default') {
            return $this->xml($this->notFound());
        }

        $destination = $this->digits((string) $request->input('destination_number', $request->input('Caller-Destination-Number', '')));
        // Kamailio (the public edge) authenticates the caller and asserts the identity
        // in X-CC-User. It strips any client-supplied copy first, and FreeSWITCH's
        // internal profile is loopback-only, so this header is trustworthy here.
        // Fall back to FreeSWITCH's own auth user for directly-authenticated legs.
        $authUser    = (string) ($request->input('variable_sip_h_X-CC-User')
                        ?: $request->input('sip_auth_username',
                           $request->input('variable_sip_auth_username', '')));
        $srcIp       = (string) ($request->input('variable_sip_h_X-CC-Src')
                        ?: $request->input('network_addr', $request->input('Caller-Network-Addr', '')));
        $uuid        = (string) $request->input('uuid', $request->input('Unique-ID', ''));
        // stable per-call billing key (falls back to a generated one if FS sent none)
        $callKey     = $uuid !== '' ? $uuid : (string) \Illuminate\Support\Str::uuid();

        // INBOUND (carrier -> DID -> registered endpoint). Kamailio tags these with
        // X-CC-Direction: inbound. They have no originating endpoint, so they take a
        // dedicated path (DID lookup) instead of the outbound rating path below.
        if (strtolower((string) $request->input('variable_sip_h_X-CC-Direction', '')) === 'inbound') {
            return $this->inboundDialplan($destination, $callKey);
        }

        // 1. resolve + authenticate the originating endpoint (by SIP user, else source IP)
        $endpoint = $this->resolveEndpoint($authUser, $srcIp);
        if (! $endpoint) {
            return $this->xml($this->reject('NO_ROUTE_DESTINATION', 'unknown endpoint'));
        }
        if ((string) $endpoint->status !== '1') {
            return $this->xml($this->reject('CALL_REJECTED', 'endpoint disabled'));
        }

        // 2. account must be active
        $account = DB::connection('switch')->table('account')->where('account_id', $endpoint->account_id)->first();
        if (! $account || (string) $account->status_id !== '1') {
            return $this->xml($this->reject('CALL_REJECTED', 'account inactive'));
        }

        // 2b. high-risk destination blocklist (toll-fraud ceiling, F5) — applies
        //     to EVERY account/billing type, before any routing or rating.
        if ($this->isBlockedDestination($destination)) {
            \Illuminate\Support\Facades\Log::warning("switch blocked high-risk destination {$destination} for {$endpoint->account_id}");
            return $this->xml($this->reject('CALL_REJECTED', 'destination blocked'));
        }

        // 3. prepaid balance gate + credit reservation (OV500's missing check).
        $prepaid = DB::connection('switch')->table('customers')->where('account_id', $endpoint->account_id)->value('billing_type') === 'prepaid';
        [$ratecardId, $rateRow] = $this->peekRate($endpoint->account_id, $destination);
        $maxSec = null;
        if ($prepaid) {
            // fail-closed: a prepaid caller with no matching customer rate is not
            // routable (would otherwise route zero-rated via a carrier prefix).
            if (! $rateRow) {
                return $this->xml($this->reject('CALL_REJECTED', 'no rate for destination'));
            }
            // reserve credit atomically so concurrent calls cannot each spend the
            // whole balance (F2). available = balance - active holds.
            $maxSec = $this->reserveCredit($endpoint->account_id, $callKey, $rateRow);
            if ($maxSec === null) {
                return $this->xml($this->reject('CALL_REJECTED', 'insufficient balance'));
            }
        }

        // 4. choose a termination carrier by least-cost among active OUTBOUND carriers
        $carrier = $this->selectCarrier($destination);
        if (! $carrier) {
            return $this->xml($this->reject('NO_ROUTE_DESTINATION', 'no carrier'));
        }
        $gateway = $this->carrierGateway($carrier);
        if (! $gateway) {
            return $this->xml($this->reject('NO_ROUTE_DESTINATION', 'carrier has no endpoint'));
        }

        // 5. digit manipulation for the chosen carrier (carrier_prefix rules)
        $dialled = $this->applyDigitManip($carrier->carrier_id, $destination);

        // 6. custom SIP headers for this endpoint
        $headers = EndpointHeader::where('sip_username', $endpoint->username)
            ->whereIn('direction', ['outbound', 'both'])->get();

        return $this->xml($this->routeXml($endpoint, $carrier, $gateway, $dialled, $headers, $maxSec, $ratecardId, $destination, $callKey, (int) ($account->account_cc ?? 0) ?: null));
    }


    /**
     * mod_xml_curl DIRECTORY binding — authenticates SIP REGISTER/INVITE.
     *
     * FreeSWITCH asks "who is <user>@<domain> and what is their password?" for
     * every registration and every authenticated request. We answer from
     * customer_sip_account, and ONLY for an active endpoint. An unknown or
     * disabled user gets a not-found document, so FreeSWITCH rejects the
     * registration — there is no anonymous or blind-auth path.
     *
     * Also emits the variables the dialplan later needs (account, channel limit),
     * so a registered leg carries its own billing identity.
     */
    public function directory(Request $request)
    {
        $user   = (string) $request->input('user', '');
        $domain = (string) $request->input('domain', '');
        $action = (string) $request->input('action', '');

        // purpose=gateways is FreeSWITCH asking for outbound gateway defs, not a user
        if ($request->input('purpose') === 'gateways' || $user === '') {
            return $this->xml($this->notFound());
        }

        $ep = SipAccount::where('username', $user)->first();
        if (! $ep || (string) $ep->status !== '1') {
            // log for fail2ban: someone probing usernames that do not exist / are off
            Log::channel('auth')->warning(
                "sip directory miss for user={$user} domain={$domain} from {$request->ip()} action={$action}"
            );
            return $this->xml($this->notFound());
        }

        // account must be active too
        $acct = DB::connection('switch')->table('account')->where('account_id', $ep->account_id)->first();
        if (! $acct || (string) $acct->status_id !== '1') {
            Log::channel('auth')->warning(
                "sip directory rejected (account inactive) user={$user} from {$request->ip()}"
            );
            return $this->xml($this->notFound());
        }

        return $this->xml($this->directoryXml($ep, $domain));
    }

    private function directoryXml(SipAccount $ep, string $domain): string
    {
        $e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_XML1);
        $dom = $e($domain !== '' ? $domain : (string) config('switch.sip_domain'));
        $cc  = max(1, (int) ($ep->sip_cc ?: 1));
        $user = $e($ep->username);
        $pass = $e($ep->secret);
        $acct = $e($ep->account_id);
        $cid  = $e($ep->caller_id ?: $ep->username);
        $cnam = $e($ep->display_name ?: $ep->username);

        // FreeSWITCH variable syntax uses ${...}; kept in a single-quoted string so
        // PHP never interpolates it.
        $dialString = '{^${regex(${sofia_contact ${dialed_user}@${dialed_domain}}|^error/)}${sofia_contact ${dialed_user}@${dialed_domain}}';

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<document type="freeswitch/xml">' . "\n";
        $xml .= '  <section name="directory">' . "\n";
        $xml .= '    <domain name="' . $dom . '">' . "\n";
        $xml .= '      <params>' . "\n";
        $xml .= '        <param name="dial-string" value="' . $e($dialString) . '"/>' . "\n";
        $xml .= '      </params>' . "\n";
        $xml .= '      <user id="' . $user . '">' . "\n";
        $xml .= '        <params>' . "\n";
        $xml .= '          <param name="password" value="' . $pass . '"/>' . "\n";
        $xml .= '        </params>' . "\n";
        $xml .= '        <variables>' . "\n";
        $xml .= '          <variable name="user_context" value="default"/>' . "\n";
        $xml .= '          <variable name="cc_account_id" value="' . $acct . '"/>' . "\n";
        $xml .= '          <variable name="cc_sip_cc" value="' . $cc . '"/>' . "\n";
        $xml .= '          <variable name="accountcode" value="' . $acct . '"/>' . "\n";
        $xml .= '          <variable name="effective_caller_id_number" value="' . $cid . '"/>' . "\n";
        $xml .= '          <variable name="effective_caller_id_name" value="' . $cnam . '"/>' . "\n";
        $xml .= '        </variables>' . "\n";
        $xml .= '      </user>' . "\n";
        $xml .= '    </domain>' . "\n";
        $xml .= '  </section>' . "\n";
        $xml .= '</document>' . "\n";

        return $xml;
    }

    /** Ring/answer/hangup call-state callback (kept minimal + safe). */
    public function event(Request $request)
    {
        // livecalls is optional bookkeeping; never trust it for money. Parameterised upsert.
        $uuid = (string) $request->input('uuid', '');
        $status = (string) $request->input('status', '');
        if ($uuid !== '') {
            DB::connection('switch')->table('livecalls')->updateOrInsert(
                ['common_uuid' => $uuid],
                ['callstatus' => substr($status, 0, 30)]
            );
        }
        return response('OK');
    }

    /**
     * CDR ingest → rate server-side → debit. Idempotent on the call UUID.
     * Accepts either flat params (a dialplan curl hook / tests) OR a FreeSWITCH
     * mod_xml_cdr document (POST field 'cdr' or the raw XML body), from which the
     * billing fields are read out of the channel <variables>.
     */
    public function cdr(Request $request, RatingService $rating)
    {
        $fields = $this->cdrFields($request);
        $uuid = (string) ($fields['call_uuid'] ?? '');
        if ($uuid === '') {
            return response('missing uuid', 422);
        }

        // The credit hold we took at call setup is the authoritative record of
        // which account this call belongs to — channel variables do not reliably
        // survive into every leg's CDR. Fall back to it when the CDR lacks one.
        $hold = \App\Models\Ov500\CreditHold::where('call_uuid', $uuid)->first();
        if (($fields['account_id'] ?? '') === '' && $hold) {
            $fields['account_id'] = $hold->account_id;
        }

        if (($fields['account_id'] ?? '') === '') {
            // not a billed leg — but make sure we never leak a reservation
            \App\Models\Ov500\CreditHold::where('call_uuid', $uuid)->delete();
            return response()->json(['rated' => false, 'reason' => 'no account (not the billed leg)']);
        }

        $rated = $rating->rateAndDebit($fields);

        return response()->json(['rated' => true, 'cost' => $rated->cost, 'billed_seconds' => $rated->billed_seconds]);
    }

    /**
     * Extract billing fields from a mod_xml_cdr document when one is present
     * (FreeSWITCH sends the XML in the body and puts only ?uuid= on the query
     * string, so the document must win), else from flat params.
     */
    private function cdrFields(Request $request): array
    {
        // Prefer the raw body whenever it carries the document: mod_xml_cdr posts
        // Content-Type application/x-www-form-plaintext, which Laravel does not
        // parse as a form, so input('cdr') may be null OR an empty string.
        $raw = (string) $request->getContent();
        $xml = str_contains($raw, '<variables') ? $raw : (string) $request->input('cdr', '');

        // The raw body arrives as "cdr=<?xml…" — strip that prefix (and url-decode
        // when encoded) or the XML will not parse.
        if (str_starts_with(ltrim($xml), 'cdr=')) {
            $xml = substr(ltrim($xml), 4);
            if (! str_starts_with(ltrim($xml), '<')) {
                $xml = urldecode($xml);
            }
        }
        $xml = ltrim($xml);
        $hasDoc = $xml !== '' && str_contains($xml, '<variables');

        // flat params (dialplan curl hook / unit tests) — only when there is no document
        if (! $hasDoc && ($request->filled('uuid') || $request->filled('call_uuid'))) {
            return [
                'call_uuid'          => (string) $request->input('uuid', $request->input('call_uuid', '')),
                'account_id'         => (string) $request->input('account_id', ''),
                'direction'          => $request->input('direction', 'outbound'),
                'source_number'      => $request->input('caller_id_number'),
                'destination_number' => $this->digits((string) $request->input('destination_number', '')),
                'carrier_id'         => $request->input('carrier_id'),
                'billsec'            => (int) $request->input('billsec', 0),
            ];
        }

        if (! $hasDoc) {
            return [];
        }
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        libxml_use_internal_errors($prev);
        if ($doc === false || ! isset($doc->variables)) {
            return [];
        }
        $v = $doc->variables;
        $get = fn ($n) => isset($v->$n) ? urldecode((string) $v->$n) : null;

        return [
            'call_uuid'          => (string) ($get('cc_call_key') ?: $get('uuid') ?: ''),
            'account_id'         => (string) ($get('cc_account_id') ?? ''),
            // our own stamp only — never FreeSWITCH's channel `direction`
            'direction'          => ($get('cc_direction') === 'inbound') ? 'inbound' : 'outbound',
            'source_number'      => $get('caller_id_number'),
            'destination_number' => $this->digits((string) ($get('cc_orig_destination') ?? $get('destination_number') ?? '')),
            'carrier_id'         => $get('cc_carrier_id'),
            'billsec'            => (int) ($get('billsec') ?? 0),
        ];
    }

    // ---- helpers -----------------------------------------------------------

    private function resolveEndpoint(string $authUser, string $srcIp): ?SipAccount
    {
        if ($authUser !== '') {
            $ep = SipAccount::where('username', $authUser)->first();
            if ($ep) return $ep;
        }
        if ($srcIp !== '') {
            return SipAccount::where('ipaddress', $srcIp)->whereIn('ipauthfrom', ['SRC', 'FROM'])->first();
        }
        return null;
    }

    private function peekRate(string $accountId, string $destination): array
    {
        // reuse RatingService's resolution via a lightweight query mirror
        $vm = DB::connection('switch')->table('customer_voipminuts')->where('account_id', $accountId)->where('status', '1')->first();
        if (! $vm) return [null, null];
        $map = DB::connection('switch')->table('tariff_ratecard_map')
            ->where('tariff_id', $vm->tariff_id)->where('ratecard_for', 'OUTGOING')->where('status', '1')
            ->orderBy('priority')->first();
        if (! $map) return [null, null];
        $row = DB::connection('switch')->table('customer_rates')
            ->where('ratecard_id', $map->ratecard_id)->where('rates_status', '1')
            ->whereRaw('? LIKE CONCAT(prefix, "%")', [$destination])
            ->orderByRaw('CHAR_LENGTH(prefix) DESC')->first();
        return [$map->ratecard_id, $row ? (array) $row : null];
    }

    private function affordableSeconds(string $balance, ?array $rateRow): int
    {
        $max = (int) config('switch.max_call_seconds', 14400);
        if (! $rateRow) return $max;
        $rate = (string) ($rateRow['rate'] ?? '0');           // per minute
        if (bccomp($rate, '0', 6) <= 0) return $max;          // free destination
        $minutes = bcdiv($balance, $rate, 6);
        $sec = (int) floor((float) bcmul($minutes, '60', 6));
        return max(1, min($sec, $max));
    }

    /**
     * Reserve prepaid credit for this call under a balance row lock. Returns the
     * capped max seconds, or null if there is no credit left once existing holds
     * are accounted for. hold_amount = worst-case cost of the granted seconds.
     * Holds older than the expiry window are ignored so orphaned holds self-heal.
     */
    private function reserveCredit(string $accountId, string $uuid, ?array $rateRow): ?int
    {
        $ttlHours = (int) config('switch.hold_ttl_hours', 6);

        return DB::connection('switch')->transaction(function () use ($accountId, $uuid, $rateRow, $ttlHours) {
            $bal = CustomerBalance::where('account_id', $accountId)->lockForUpdate()->first();
            $balance = $bal ? (string) $bal->balance : '0';

            $committed = (string) (\App\Models\Ov500\CreditHold::where('account_id', $accountId)
                ->where('created_at', '>', now()->subHours($ttlHours))
                ->where('call_uuid', '!=', $uuid)
                ->sum('hold_amount') ?? '0');

            $available = bcsub($balance, $committed, 6);
            if (bccomp($available, '0', 6) <= 0) {
                return null;
            }

            $maxSec = $this->affordableSeconds($available, $rateRow);

            // Hold only the cost of an initial window, not of the whole affordable
            // duration — otherwise a single call reserves the entire balance and no
            // second concurrent call is ever permitted. The maxSec cap still stops
            // any one call exceeding the balance.
            $window = min($maxSec, (int) config('switch.hold_window_seconds', 300));
            $rate = (string) ($rateRow['rate'] ?? '0');
            $hold = bcmul($rate, bcdiv((string) $window, '60', 8), 6);
            if (bccomp($hold, $available, 6) > 0) {
                $hold = $available;
            }

            \App\Models\Ov500\CreditHold::updateOrCreate(
                ['call_uuid' => $uuid],
                ['account_id' => $accountId, 'hold_amount' => $hold, 'created_at' => now()]
            );

            return $maxSec;
        });
    }

    /** Least-cost active OUTBOUND carrier whose carrier_rates has a matching prefix. */
    private function selectCarrier(string $destination): ?Carrier
    {
        // carrier -> tariff_ratecard_map (its CARRIER tariff) -> carrier_rates on the mapped ratecard
        $best = DB::connection('switch')->table('carrier')
            ->join('tariff_ratecard_map as trm', function ($j) {
                $j->on('trm.tariff_id', '=', 'carrier.tariff_id')->where('trm.status', '1');
            })
            ->join('carrier_rates as cr', function ($j) {
                $j->on('cr.ratecard_id', '=', 'trm.ratecard_id')->where('cr.rates_status', '1');
            })
            ->where('carrier.carrier_status', 1)
            ->where('carrier.carrier_type', 'OUTBOUND')
            ->whereRaw('? LIKE CONCAT(cr.prefix, "%")', [$destination])
            ->orderBy('cr.rate')
            ->orderByRaw('CHAR_LENGTH(cr.prefix) DESC')
            ->select('carrier.*')
            ->first();
        if ($best) return Carrier::hydrate([(array) $best])->first();

        $default = (string) config('switch.default_carrier_id');
        if ($default !== '') {
            return Carrier::where('carrier_id', $default)->where('carrier_status', 1)->first();
        }
        return null;
    }

    private function carrierGateway(Carrier $carrier): ?object
    {
        return DB::connection('switch')->table('carrier_ips')
            ->where('carrier_id', $carrier->carrier_id)
            ->where('ip_status', '1')
            ->orderBy('priority')
            ->first();
    }

    private function applyDigitManip(string $carrierId, string $destination): string
    {
        $rule = DB::connection('switch')->table('carrier_prefix')
            ->where('carrier_id', $carrierId)->where('route', 'OUTBOUND')
            ->whereRaw('? LIKE CONCAT(maching_string, "%")', [$destination])
            ->orderByRaw('CHAR_LENGTH(maching_string) DESC')->first();
        if (! $rule) return $destination;

        // OV500 uses '%' as a wildcard / no-op sentinel in these columns.
        $remove = ($rule->remove_string === '%' ? '' : (string) $rule->remove_string);
        $add    = ($rule->add_string === '%' ? '' : (string) $rule->add_string);

        $out = $destination;
        if ($remove !== '' && str_starts_with($out, $remove)) {
            $out = substr($out, strlen($remove));
        }
        if ($add !== '') {
            $out = $add . $out;
        }
        // defensive: a dial string is telephony chars only (never a stray wildcard)
        return preg_replace('/[^0-9+*#]/', '', $out) ?? $out;
    }

    private function digits(string $v): string
    {
        return preg_replace('/[^0-9]/', '', $v) ?? '';
    }

    private function xml(string $body)
    {
        return response($body, 200)->header('Content-Type', 'text/xml');
    }

    /** Tell mod_xml_curl "not my context" so FreeSWITCH uses its static dialplan. */
    private function notFound(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<document type="freeswitch/xml"><section name="result">'
            . '<result status="not found"/></section></document>';
    }

    /**
     * True if the destination matches a configured high-risk prefix (F5).
     * Normalises a leading +, 00 or 0011 international access code first.
     */
    private function isBlockedDestination(string $destination): bool
    {
        $blocked = (array) config('switch.blocked_prefixes', []);
        if (! $blocked) { return false; }
        $d = ltrim($destination, '+');
        if (str_starts_with($d, '0011')) { $d = substr($d, 4); }
        elseif (str_starts_with($d, '00')) { $d = substr($d, 2); }
        foreach ($blocked as $p) {
            if ($p !== '' && str_starts_with($d, (string) $p)) { return true; }
        }
        return false;
    }

    /**
     * Inbound DID call (carrier -> Kamailio -> here). Route the dialed DID to the
     * customer's registered SIP endpoint by bridging back through Kamailio's usrloc
     * (sofia/internal/<user>@127.0.0.1:5060). Billed via the CDR as an inbound leg
     * (direction=inbound -> INCOMING ratecard). Ringing is never gated on balance.
     */
    private function inboundDialplan(string $did, string $callKey)
    {
        $sw = DB::connection('switch');
        $didRow = $sw->table('did')->where('did_number', $did)->first();
        if (! $didRow || empty($didRow->account_id) || in_array((string) $didRow->did_status, ['DEAD', 'BLOCKED'], true)) {
            return $this->xml($this->reject('NO_ROUTE_DESTINATION', "no active DID {$did}"));
        }
        $account = $sw->table('account')->where('account_id', $didRow->account_id)->first();
        if (! $account || (string) $account->status_id !== '1') {
            return $this->xml($this->reject('CALL_REJECTED', 'DID account inactive'));
        }
        $dst = $sw->table('did_dst')->where('did_number', $did)->first();
        if (! $dst || (string) $dst->dst_type !== 'CUSTOMER' || blank($dst->dst_destination)) {
            return $this->xml($this->reject('NO_ROUTE_DESTINATION', 'DID has no customer-endpoint route'));
        }
        $ep = $sw->table('customer_sip_account')
            ->where('username', $dst->dst_destination)->where('account_id', $didRow->account_id)->first();
        if (! $ep || (string) $ep->status !== '1') {
            return $this->xml($this->reject('CALL_REJECTED', 'target endpoint unavailable'));
        }
        \Illuminate\Support\Facades\Log::info("switch inbound DID {$did} -> {$ep->username} (acct {$didRow->account_id})");
        return $this->xml($this->inboundXml($did, $didRow->account_id, $ep->username, (int) ($ep->sip_cc ?: 1), $callKey, (string) ($didRow->carrier_id ?? '')));
    }

    private function inboundXml(string $did, string $accountId, string $epUser, int $cc, string $callKey, string $carrierId = ''): string
    {
        $e = fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES | ENT_XML1);
        $vars = [];
        $vars[] = '<action application="export" data="cc_call_key=' . $e($callKey) . '"/>';
        $vars[] = '<action application="export" data="cc_account_id=' . $e($accountId) . '"/>';
        $vars[] = '<action application="export" data="cc_direction=inbound"/>';
        // the DID's provider carrier, so inbound CDRs attribute to the inbound trunk
        if ($carrierId !== '') {
            $vars[] = '<action application="export" data="cc_carrier_id=' . $e($carrierId) . '"/>';
        }
        $vars[] = '<action application="export" data="cc_orig_destination=' . $e($did) . '"/>';
        $vars[] = '<action application="set" data="hangup_after_bridge=true"/>';
        $vars[] = '<action application="set" data="continue_on_fail=false"/>';
        $vars[] = '<action application="limit" data="hash ccportal_cc ' . $e($epUser) . ' ' . $cc . ' !USER_BUSY"/>';
        // deliver to the registered endpoint via Kamailio usrloc (loopback leg)
        $vars[] = '<action application="bridge" data="sofia/internal/' . $e($epUser) . '@127.0.0.1:5060"/>';
        $varsXml = implode("\n          ", $vars);
        $didE = $e($did);
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<document type="freeswitch/xml">
  <section name="dialplan">
    <context name="default">
      <extension name="cc_inbound">
        <condition field="destination_number" expression="^\+?{$didE}$">
          {$varsXml}
        </condition>
      </extension>
    </context>
  </section>
</document>
XML;
    }

    private function reject(string $cause, string $note): string
    {
        $cause = htmlspecialchars($cause, ENT_QUOTES | ENT_XML1);
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<document type="freeswitch/xml">
  <section name="dialplan">
    <context name="default">
      <extension name="cc_reject">
        <condition>
          <action application="respond" data="$cause"/>
          <action application="hangup" data="$cause"/>
        </condition>
      </extension>
    </context>
  </section>
</document>
XML;
    }

    private function routeXml($endpoint, Carrier $carrier, object $gw, string $dialled, $headers, ?int $maxSec, ?string $ratecardId, string $origDest, string $callKey, ?int $account_cc = null): string
    {
        $e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_XML1);
        $gwHost = $e($gw->ipaddress);
        // Per-leg channel variables in [ ] apply to the outbound leg only. Enforce
        // the carrier's agreed codec list there (carrier.carrier_codecs) so the
        // termination leg offers exactly what that carrier accepts — without this
        // the B-leg inherits whatever the A-leg negotiated and can fail with
        // INCOMPATIBLE_DESTINATION.
        $legVars = '';
        $codecs = trim((string) $carrier->carrier_codecs);
        if ($codecs !== '') {
            $safe = preg_replace('/[^A-Za-z0-9,.@]/', '', $codecs);
            if ($safe !== '') {
                $legVars = "[absolute_codec_string='{$e($safe)}']";
            }
        }
        $directBridge = "{$legVars}sofia/external/{$e($dialled)}@{$gwHost}";

        // 'export' (not 'set') so the billing variables propagate to the outbound
        // leg as well — whichever leg mod_xml_cdr reports must carry them, or the
        // CDR arrives with no account and cannot be rated.
        $vars = [];
        $vars[] = '<action application="export" data="cc_account_id=' . $e($endpoint->account_id) . '"/>';
        $vars[] = '<action application="export" data="cc_carrier_id=' . $e($carrier->carrier_id) . '"/>';
        $vars[] = '<action application="export" data="cc_ratecard_id=' . $e($ratecardId) . '"/>';
        $vars[] = '<action application="export" data="cc_orig_destination=' . $e($origDest) . '"/>';
        // Billing direction, stamped by us. FreeSWITCH's own `direction` variable
        // describes the CHANNEL (a customer's outbound call is an "inbound" channel
        // to FS), so it must NOT be used to pick the ratecard.
        $vars[] = '<action application="export" data="cc_direction=outbound"/>';
        // Call-level billing key. mod_xml_cdr posts one CDR PER LEG, each with its
        // own channel uuid — keying idempotency on the leg uuid would bill the same
        // call once per leg. Every leg of this call carries the same cc_call_key, so
        // UNIQUE(call_uuid) collapses them to a single rated CDR and one debit.
        $vars[] = '<action application="export" data="cc_call_key=' . $e($callKey) . '"/>';
        $vars[] = '<action application="set" data="hangup_after_bridge=true"/>';
        // Enforce the endpoint's concurrent-call ceiling (customer_sip_account.sip_cc).
        // Stored but never applied before — on a public SIP port a stolen credential
        // could otherwise open unlimited simultaneous calls. `limit` is released
        // automatically when the channel ends.
        $cc = max(1, (int) ($endpoint->sip_cc ?: 1));
        $vars[] = '<action application="limit" data="hash ccportal_cc ' . $e($endpoint->username) . ' ' . $cc . ' !USER_BUSY"/>';
        // and a per-account CPS guard
        $vars[] = '<action application="limit" data="hash ccportal_acct ' . $e($endpoint->account_id) . ' ' . max(1, (int) ($account_cc ?? config('switch.default_account_cc', 2))) . ' !USER_BUSY"/>';
        // Absolute duration cap on EVERY call, fail-closed (F4): prepaid uses
        // its credit-bounded $maxSec; every other billing type gets the hard
        // ceiling so an unknown/postpaid account can never run unbounded.
        $capSec = $maxSec !== null
            ? min((int) $maxSec, (int) config('switch.max_call_seconds', 14400))
            : (int) config('switch.default_max_call_seconds', 3600);
        if ($capSec > 0) {
            $vars[] = '<action application="set" data="max_forwarded_seconds=' . $capSec . '"/>';
            $vars[] = '<action application="sched_hangup" data="+' . $capSec . ' allotted_timeout"/>';
        }
        // custom SIP headers -> sip_h_ on the outbound leg
        foreach ($headers as $h) {
            $vars[] = '<action application="set" data="sip_h_' . $e($h->header_name) . '=' . $e($h->header_value) . '"/>';
        }
        $caller = $e($endpoint->caller_id ?: $endpoint->username);
        $vars[] = '<action application="set" data="effective_caller_id_number=' . $caller . '"/>';
        $varsXml = implode("\n          ", $vars);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<document type="freeswitch/xml">
  <section name="dialplan">
    <context name="default">
      <extension name="cc_outbound">
        <condition field="destination_number" expression="^\+?{$e($origDest)}$">
          $varsXml
          <action application="bridge" data="$directBridge"/>
        </condition>
      </extension>
    </context>
  </section>
</document>
XML;
    }
}
