<?php

namespace App\Services;

use App\Models\Ov500\Account;
use App\Models\Ov500\BalanceLedger;
use App\Models\Ov500\CustomerBalance;
use App\Models\Ov500\PaymentHistory;
use Illuminate\Support\Facades\DB;

/**
 * Balance mutations, done the way OV500 did not:
 *   - the caller has already been authorized (policy) BEFORE we get here;
 *   - the whole thing runs in ONE transaction on the `switch` connection, so
 *     ledger + balance + payment_history commit together or not at all;
 *   - the balance row is SELECT ... FOR UPDATE locked, so concurrent top-ups
 *     cannot race the read-modify-write (OV500 read then wrote, unlocked);
 *   - idempotency_key is UNIQUE, so a double-submit / retry credits once;
 *   - money is bcmath strings, never PHP floats.
 *
 * Contrast: OV500's crs Payment credited the balance, THEN (maybe) checked
 * ownership, with no idempotency and no lock — the source of C17/H35/C9/C19.
 */
class BalanceService
{
    private const SCALE = 6;

    /**
     * @throws \Illuminate\Database\QueryException on a duplicate idempotency_key
     *         (unique violation) — the caller treats that as "already applied".
     */
    public function topUp(
        Account $account,
        string $amount,
        string $idempotencyKey,
        string $actor,
        ?string $notes = null,
        string $paymentOption = 'ADDBALANCE',
    ): BalanceLedger {
        // Fast path: this exact operation already happened — return it, no re-credit.
        $existing = BalanceLedger::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        return DB::connection('switch')->transaction(function () use (
            $account, $amount, $idempotencyKey, $actor, $notes, $paymentOption
        ) {
            // Lock (or create) the balance row.
            $bal = CustomerBalance::where('account_id', $account->account_id)
                ->lockForUpdate()
                ->first();

            if (! $bal) {
                $bal = new CustomerBalance([
                    'account_id'   => $account->account_id,
                    'balance'      => '0',
                    'credit_limit' => '0',
                    'service_type' => 'SWITCH',
                ]);
                $bal->save();
                $bal = CustomerBalance::where('account_id', $account->account_id)->lockForUpdate()->first();
            }

            $before = $this->norm($bal->balance);
            $after  = bcadd($before, $amount, self::SCALE);

            // Ledger first: the UNIQUE(idempotency_key) is the hard double-credit
            // guard even against a simultaneous request that passed the fast path.
            $ledger = BalanceLedger::create([
                'idempotency_key'  => $idempotencyKey,
                'account_id'       => $account->account_id,
                'kind'             => 'topup',
                'amount'           => $amount,
                'balance_before'   => $before,
                'balance_after'    => $after,
                'actor'            => $actor,
                'notes'            => $notes,
                'created_at'       => now(),
            ]);

            $bal->balance = $after;
            $bal->save();

            $payment = PaymentHistory::create([
                'account_id'       => $account->account_id,
                'payment_option_id'=> $paymentOption,
                'amount'           => $amount,
                'paid_on'          => now(),
                'notes'            => $notes,
                'transaction_id'   => $idempotencyKey,
                'created_by'       => $actor,
                'create_dt'        => now(),
                // NOT NULL / no default under STRICT mode
                'file_name'        => '',
                'other_data'       => '',
                'invoice_data'     => '',
            ]);

            $ledger->payment_history_id = $payment->payment_id;
            $ledger->save();

            return $ledger;
        });
    }

    private function norm(?string $v): string
    {
        return bcadd($v === null || $v === '' ? '0' : (string) $v, '0', self::SCALE);
    }
}
