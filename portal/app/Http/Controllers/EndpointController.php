<?php

namespace App\Http\Controllers;

use App\Models\Ov500\Account;
use App\Models\Ov500\EndpointHeader;
use App\Models\Ov500\SipAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EndpointController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SipAccount::class);
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));

        $endpoints = SipAccount::query()
            ->when(! $user->isAdmin(), fn ($x) => $x->whereIn('account_id', $user->accessibleAccountIds()))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) =>
                $w->where('username', 'like', "%{$q}%")->orWhere('display_name', 'like', "%{$q}%")))
            ->orderBy('username')
            ->paginate(30)->withQueryString();

        // live registration state (aor -> ip/ua/expires) from the root stats snapshot
        $regs = $this->registrations();

        return view('endpoints.index', compact('endpoints', 'q', 'regs'));
    }

    /**
     * Kamailio usrloc registrations, keyed by lower-cased AoR, from the root
     * monitoring snapshot (/var/lib/ccportal/stats.json). The web process has no
     * kamcmd rights — it only reads the file the cc-stats timer writes.
     */
    private function registrations(): array
    {
        $file = '/var/lib/ccportal/stats.json';
        if (! is_readable($file)) {
            return [];
        }
        $data = json_decode((string) @file_get_contents($file), true) ?: [];
        $out = [];
        foreach ($data['registrations'] ?? [] as $r) {
            $aor = strtolower((string) ($r['aor'] ?? ''));
            if ($aor !== '') {
                $out[$aor] = ['ip' => $r['ip'] ?? '', 'ua' => $r['ua'] ?? '', 'expires' => $r['expires'] ?? ''];
            }
        }
        return $out;
    }

    public function create(Request $request)
    {
        $this->authorize('create', SipAccount::class);
        return view('endpoints.form', [
            'endpoint' => new SipAccount(['status' => '1', 'ipauthfrom' => 'NO', 'sip_cc' => 1, 'sip_cps' => 1, 'cli_prefer' => 'rpid', 'codecs' => 'G729,PCMU,PCMA']),
            'accounts' => $this->accountsFor($request),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', SipAccount::class);
        $data = $this->validated($request);
        $this->assertAccountInScope($request, $data['account_id']);
        $actor = $request->user()->email;
        $ep = null;

        DB::connection('switch')->transaction(function () use (&$ep, $data, $actor, $request) {
            $ep = new SipAccount($data);
            $ep->extension_id = 'EXT' . strtoupper(Str::random(8));
            $ep->created_by = $actor;
            $ep->created_by_account_id = $request->user()->account_id ?: 'SYSTEM';
            $ep->updated_by = $actor;
            $ep->created_dt = now();
            $ep->updated_dt = now();
            $ep->user_type = 'SWITCH';
            $ep->save();
            $this->syncHeaders($request, $ep, $actor);
        });

        return redirect()->route('endpoints.show', $ep)->with('status', "Endpoint {$ep->username} created.");
    }

    public function show(SipAccount $endpoint)
    {
        $this->authorize('view', $endpoint);
        $endpoint->load('headers');

        // Registration details for the device-configuration panel. Read from config
        // rather than hard-coded in the view so the panel can never drift from what
        // Kamailio actually listens on.
        $sip = [
            'domain'   => config('switch.sip_domain'),
            'proxy'    => config('switch.sip_proxy'),
            'port'     => config('switch.sip_port'),
            'tls_port' => config('switch.sip_tls_port'),
        ];

        return view('endpoints.show', compact('endpoint', 'sip'));
    }

    /**
     * Reveal the SIP secret on explicit operator request. Authorized (same as
     * viewing the endpoint) and audit-logged — a SIP secret is an operational
     * credential the operator needs to configure the device, but reads are
     * deliberate and recorded, not incidental.
     */
    public function secret(Request $request, SipAccount $endpoint)
    {
        $this->authorize('view', $endpoint);
        // reveal is an operator action — admin/reseller only, never the customer role
        abort_unless(in_array($request->user()->role, ['admin', 'reseller'], true), 403);
        Log::info('endpoint.secret.revealed', [
            'endpoint' => $endpoint->username,
            'by'       => $request->user()->email,
            'ip'       => $request->ip(),
        ]);
        return response()->json(['secret' => $endpoint->secret]);
    }

    public function edit(Request $request, SipAccount $endpoint)
    {
        $this->authorize('update', $endpoint);
        return view('endpoints.form', [
            'endpoint' => $endpoint,
            'accounts' => $this->accountsFor($request),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, SipAccount $endpoint)
    {
        $this->authorize('update', $endpoint);
        $data = $this->validated($request, $endpoint);
        $this->assertAccountInScope($request, $data['account_id']);

        // blank secret on edit keeps the existing one
        if (blank($data['secret'])) {
            unset($data['secret']);
        }
        DB::connection('switch')->transaction(function () use ($endpoint, $data, $request) {
            $endpoint->fill($data);
            $endpoint->updated_by = $request->user()->email;
            $endpoint->updated_dt = now();
            $endpoint->save();
            $this->syncHeaders($request, $endpoint, $request->user()->email);
        });

        return redirect()->route('endpoints.show', $endpoint)->with('status', 'Endpoint updated.');
    }

    // ---- helpers -----------------------------------------------------------

    private function validated(Request $request, ?SipAccount $ep = null): array
    {
        $usernameRule = Rule::unique('switch.customer_sip_account', 'username');
        if ($ep) {
            $usernameRule = $usernameRule->ignore($ep->id, 'id');
        }

        return $request->validate([
            'username'    => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/', $usernameRule],
            // public SIP port: a weak secret is a toll-fraud vector, so require length +
            // mixed character classes. Max 30 is the schema column width.
            'secret'      => [$ep ? 'nullable' : 'required', 'string', 'min:16', 'max:30', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d._~!@#%^*+=-]+$/'],
            'account_id'  => ['required', Rule::exists('switch.account', 'account_id')],
            'display_name'=> ['nullable', 'string', 'max:30'],
            'name'        => ['required', 'string', 'max:100'],
            'email_address'=> ['required', 'email', 'max:150'],
            'phone_number'=> ['required', 'string', 'max:20'],
            'ipaddress'   => ['nullable', 'ip'],
            'ipauthfrom'  => ['required', Rule::in(['FROM', 'SRC', 'NO'])],
            'sip_cc'      => ['required', 'integer', 'min:1', 'max:10000'],
            'sip_cps'     => ['required', 'integer', 'min:1', 'max:1000'],
            'codecs'      => ['nullable', 'string', 'max:50'],
            'caller_id'   => ['nullable', 'string', 'max:150', 'regex:/^[A-Za-z0-9 +._@-]*$/'],
            'cli_prefer'  => ['required', Rule::in(['rpid', 'pid', 'no'])],
            'status'      => ['required', Rule::in(['0', '1'])],
            'call_recording' => ['required', Rule::in(['0', '1'])],
            'dnd'         => ['required', Rule::in(['Y', 'N'])],
        ]);
    }

    /**
     * Replace the endpoint's custom SIP headers with the submitted set. Blank
     * rows (the form always renders one spare) are dropped. Runs inside the
     * caller's transaction.
     */
    private function syncHeaders(Request $request, SipAccount $ep, string $actor): void
    {
        $rows = collect((array) $request->input('headers', []))
            ->reject(fn ($r) => blank($r['name'] ?? null) && blank($r['value'] ?? null))
            ->values();

        $validator = validator(['headers' => $rows->all()], [
            'headers.*.name'      => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9-]+$/'],
            // printable, but no quotes/angle-brackets/controls that could break XML attrs
            'headers.*.value'     => ['required', 'string', 'max:255', 'regex:/^[^"\'<>\r\n]+$/'],
            'headers.*.direction' => ['nullable', Rule::in(['inbound', 'outbound', 'both'])],
        ]);
        $validator->validate();

        $keep = [];
        foreach ($rows as $r) {
            $name = $r['name'];
            $keep[] = $name;
            EndpointHeader::updateOrCreate(
                ['sip_username' => $ep->username, 'header_name' => $name],
                [
                    'account_id'   => $ep->account_id,
                    'header_value' => $r['value'],
                    'direction'    => $r['direction'] ?? 'outbound',
                    'created_by'   => $actor,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]
            );
        }
        EndpointHeader::where('sip_username', $ep->username)
            ->when($keep, fn ($q) => $q->whereNotIn('header_name', $keep))
            ->delete();
    }

    private function accountsFor(Request $request)
    {
        $user = $request->user();
        return Account::query()
            ->when(! $user->isAdmin(), fn ($x) => $x->whereIn('account_id', $user->accessibleAccountIds()))
            ->orderBy('account_id')->limit(500)->get();
    }

    private function assertAccountInScope(Request $request, string $accountId): void
    {
        if (! $request->user()->isAdmin()
            && ! in_array($accountId, $request->user()->accessibleAccountIds(), true)) {
            abort(403, 'Account outside your scope.');
        }
    }
}
