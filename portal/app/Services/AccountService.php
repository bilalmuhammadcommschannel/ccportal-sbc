<?php

namespace App\Services;

use App\Models\Ov500\Account;
use App\Models\Ov500\CustomerBalance;
use App\Models\Ov500\CustomerProfile;
use App\Models\Ov500\CustomerVoipMinute;
use App\Models\Ov500\ResellerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Creates OV500 accounts (customer or reseller) as one atomic unit across the
 * several tables OV500 spreads an account over. Everything runs in a single
 * `switch`-connection transaction, so a half-created account is impossible —
 * the OV500 portal inserted these piecemeal with no transaction.
 *
 *   customer  -> account + customers + customer_balance + customer_voipminuts(tariff)
 *   reseller  -> account + resellers  + customer_balance
 *
 * account_id is generated under a row lock (MAX(suffix)+1 per prefix) so two
 * concurrent creates cannot collide.
 */
class AccountService
{
    private const PLATFORM = 'SYSTEM';
    private const CUST_FLOOR = 300000;
    private const RES_FLOOR = 100000;

    public function createCustomer(array $data, User $actor): Account
    {
        return DB::connection('switch')->transaction(function () use ($data, $actor) {
            $accountId = $this->nextAccountId('STC', 'CUSTOMER', self::CUST_FLOOR);
            $parent = $actor->account_id ?: self::PLATFORM;

            $account = Account::create([
                'account_id'        => $accountId,
                'account_type'      => 'CUSTOMER',
                'parent_account_id' => $parent,
                'status_id'         => '1',
                'currency_id'       => $data['currency_id'],
                'account_cc'        => $data['account_cc'] ?? null,
                'account_cps'       => $data['account_cps'] ?? null,
                'tax_type'          => $data['tax_type'] ?? 'exclusive',
                'create_dt'         => now(),
                'create_by'         => $actor->email,
                'update_by'         => $actor->email,
            ]);

            CustomerProfile::create([
                'account_id'    => $accountId,
                'company_name'  => $data['company_name'],
                'contact_name'  => $data['contact_name'] ?? null,
                'name'          => $data['name'] ?? null,
                'phone'         => $data['phone'] ?? null,
                'emailaddress'  => $data['emailaddress'] ?? null,
                'billing_type'  => $data['billing_type'] ?? 'prepaid',
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'created_by'    => $actor->email,
                'created_dt'    => now(),
            ]);

            CustomerBalance::create([
                'account_id'   => $accountId,
                'balance'      => '0',
                'credit_limit' => '0',
                'service_type' => 'SWITCH',
            ]);

            if (! empty($data['tariff_id'])) {
                $this->assignTariff($accountId, 'CUSTOMER', $data['tariff_id'], $actor->email);
            }

            // Every customer gets one ready-to-use SIP endpoint: username =
            // <company>_<5 rand>, a strong random secret (password auth). Both are
            // returned on the (non-persisted) account so the caller can show them once.
            [$epUser, $epSecret] = $this->createDefaultEndpoint($accountId, $data, $actor);
            $account->new_endpoint_username = $epUser;
            $account->new_endpoint_secret = $epSecret;

            return $account;
        });
    }

    /**
     * Provision the customer's first SIP endpoint (customer_sip_account).
     * Returns [username, plaintext secret] — the secret is a SIP shared secret
     * (stored as-is for FreeSWITCH digest auth), shown to the operator once.
     */
    private function createDefaultEndpoint(string $accountId, array $data, User $actor): array
    {
        $sw = DB::connection('switch');

        // username: <sanitised company>_<5 lower-alnum>, unique, <=30 chars
        $base = preg_replace('/[^A-Za-z0-9]/', '', (string) ($data['company_name'] ?? ''));
        if ($base === '') {
            $base = 'cust';
        }
        $base = substr($base, 0, 20);
        do {
            $username = $base . '_' . $this->randToken(5, 'abcdefghijkmnpqrstuvwxyz0123456789');
        } while ($sw->table('customer_sip_account')->where('username', $username)->exists());

        // secret: 18 chars, guaranteed lower+upper+digit (endpoint policy)
        do {
            $secret = $this->randToken(18, 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789');
        } while (! preg_match('/[a-z]/', $secret) || ! preg_match('/[A-Z]/', $secret) || ! preg_match('/\d/', $secret));

        $sw->table('customer_sip_account')->insert([
            'username'              => $username,
            'secret'                => $secret,
            'account_id'            => $accountId,
            'status'                => '1',
            'ipauthfrom'            => 'NO',            // password auth
            'sip_cc'                => (int) ($data['account_cc'] ?? 0) ?: 10,
            'sip_cps'               => 1,
            'codecs'                => 'PCMU,PCMA',     // G.711 a-law + u-law (house standard)
            'cli_prefer'            => 'rpid',
            'display_name'          => substr((string) ($data['company_name'] ?? $accountId), 0, 30),
            'name'                  => (string) ($data['company_name'] ?? $accountId),
            'email_address'         => $data['emailaddress'] ?? ($accountId . '@ccportal.local'),
            'phone_number'          => (string) ($data['phone'] ?? ''),
            'call_recording'        => '0',
            'dnd'                   => 'N',
            'user_type'             => 'SWITCH',
            'extension_id'          => 'EXT' . strtoupper($this->randToken(8, 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789')),
            'created_by'            => $actor->email,
            'created_by_account_id' => $actor->account_id ?: self::PLATFORM,
            'updated_by'            => $actor->email,
            'created_dt'            => now(),
            'updated_dt'            => now(),
        ]);

        Log::info("auto-provisioned endpoint {$username} for {$accountId} by {$actor->email}");
        return [$username, $secret];
    }

    /** Cryptographically-random token of $len chars from $alphabet. */
    private function randToken(int $len, string $alphabet): string
    {
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }

    public function createReseller(array $data, User $actor): Account
    {
        return DB::connection('switch')->transaction(function () use ($data, $actor) {
            $accountId = $this->nextAccountId('STR', 'RESELLER', self::RES_FLOOR);
            $parent = $actor->account_id ?: self::PLATFORM;

            $account = Account::create([
                'account_id'        => $accountId,
                'account_type'      => 'RESELLER',
                'parent_account_id' => $parent,
                'status_id'         => '1',
                'currency_id'       => $data['currency_id'],
                'tax_type'          => $data['tax_type'] ?? 'exclusive',
                'create_dt'         => now(),
                'create_by'         => $actor->email,
                'update_by'         => $actor->email,
            ]);

            ResellerProfile::create([
                'account_id'   => $accountId,
                'company_name' => $data['company_name'],
                'contact_name' => $data['contact_name'] ?? null,
                'phone'        => $data['phone'] ?? null,
                'emailaddress' => $data['emailaddress'] ?? null,
            ]);

            CustomerBalance::create([
                'account_id'   => $accountId,
                'balance'      => '0',
                'credit_limit' => '0',
                'service_type' => 'SWITCH',
            ]);

            return $account;
        });
    }

    /**
     * Remove a customer safely. A customer with NO dependents and NO financial
     * history is hard-deleted (its account/profile/balance/tariff rows dropped).
     * Anything with endpoints, DIDs, sub-accounts, rated calls, a billing ledger,
     * bundles or a non-zero balance is instead ARCHIVED — account status set to
     * Closed (-3) and its endpoints disabled so it can no longer register or
     * place calls — with every financial record preserved for audit/accounting.
     * All of it runs in one `switch`-connection transaction.
     *
     * @return array{action:'deleted'|'archived', blockers:string[]}
     */
    public function deleteCustomer(Account $customer, User $actor): array
    {
        return DB::connection('switch')->transaction(function () use ($customer, $actor) {
            $id = $customer->account_id;
            $sw = DB::connection('switch');

            // What makes a hard delete unsafe? Collect human-readable reasons.
            $blockers = [];
            if (($n = $sw->table('customer_sip_account')->where('account_id', $id)->count()) > 0) {
                $blockers[] = "{$n} endpoint(s)";
            }
            if (($n = $sw->table('did')->where('account_id', $id)->count()) > 0) {
                $blockers[] = "{$n} DID(s)";
            }
            if (($n = $sw->table('account')->where('parent_account_id', $id)->count()) > 0) {
                $blockers[] = "{$n} sub-account(s)";
            }
            if (($n = $sw->table('switchcdr.cc_rated_cdr')->where('account_id', $id)->count()) > 0) {
                $blockers[] = "{$n} rated call(s)";
            }
            if ($sw->table('cc_balance_ledger')->where('account_id', $id)->exists()) {
                $blockers[] = 'billing history';
            }
            if (($n = $sw->table('bundle_account')->where('account_id', $id)->count()) > 0) {
                $blockers[] = "{$n} bundle(s)";
            }
            $bal = (string) ($sw->table('customer_balance')->where('account_id', $id)->value('balance') ?? '0');
            if (bccomp($bal, '0', 6) !== 0) {
                $blockers[] = "non-zero balance ({$bal})";
            }

            if ($blockers) {
                // ARCHIVE: keep every record, stop the service.
                $sw->table('account')->where('account_id', $id)->update(['status_id' => '-3']);
                $sw->table('customer_sip_account')->where('account_id', $id)->update(['status' => '0']);
                Log::channel('auth')->warning(
                    "customer archived {$id} by {$actor->email} — has ".implode(', ', $blockers)
                );

                return ['action' => 'archived', 'blockers' => $blockers];
            }

            // HARD DELETE: nothing of value is attached.
            $sw->table('customer_voipminuts')->where('account_id', $id)->delete();
            $sw->table('cc_credit_holds')->where('account_id', $id)->delete();
            $sw->table('customer_balance')->where('account_id', $id)->delete();
            $sw->table('customers')->where('account_id', $id)->delete();
            $sw->table('account')->where('account_id', $id)->delete();
            Log::channel('auth')->warning(
                "customer permanently deleted {$id} by {$actor->email} (no dependents or history)"
            );

            return ['action' => 'deleted', 'blockers' => []];
        });
    }

    public function assignTariff(string $accountId, string $accountType, string $tariffId, string $actor): void
    {
        CustomerVoipMinute::updateOrCreate(
            ['account_id' => $accountId],
            [
                'customer_voipminute_id' => 'VM' . strtoupper(Str::random(8)),
                'account_type' => $accountType,
                'tariff_id'    => $tariffId,
                'status'       => '1',
                'created_by'   => $actor,
                'updated_by'   => $actor,
                'created_dt'   => now(),
                'updated_dt'   => now(),
            ]
        );
    }

    /** MAX(numeric suffix)+1 for the prefix, under a row lock to avoid collisions. */
    private function nextAccountId(string $prefix, string $type, int $floor): string
    {
        $max = (int) DB::connection('switch')->table('account')
            ->where('account_type', $type)
            ->where('account_id', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->selectRaw('MAX(CAST(SUBSTRING(account_id, 4) AS UNSIGNED)) as m')
            ->value('m');

        $next = max($max + 1, $floor + 1);
        return $prefix . $next;
    }
}
