<?php

namespace App\Services;

use App\Models\Ov500\BalanceLedger;
use App\Models\Ov500\CreditHold;
use App\Models\Ov500\CustomerBalance;
use App\Models\Ov500\CustomerVoipMinute;
use App\Models\Ov500\RatedCdr;
use App\Models\Ov500\TariffRatecardMap;
use Illuminate\Support\Facades\DB;

/**
 * The rating engine — the piece OV500 got wrong (client-supplied cost, no
 * idempotency, no lock, float money). Here:
 *   - cost is ALWAYS recomputed server-side from the customer's own ratecard;
 *     nothing from the CDR payload is trusted for pricing;
 *   - pulse billing: first `minimal_time` block, then `resolution_time` steps;
 *   - grace_period, setup_charge, connection_charge, minimal_charge honoured;
 *   - rating is idempotent on the call UUID (UNIQUE) so a re-POSTed CDR rates once;
 *   - the balance debit runs in one transaction with a row lock and floors at 0.
 * All money is bcmath (scale 6), never PHP floats.
 */
class RatingService
{
    private const SCALE = 6;

    /**
     * Pure pulse maths. Given billsec and a rate row, return
     * [billed_seconds, cost] as strings. No DB, no side effects — unit-testable.
     */
    public function computeCost(int $billsec, array $r): array
    {
        $rate     = $this->s($r['rate'] ?? '0');            // per-minute
        $minTime  = max(0, (int) ($r['minimal_time'] ?? 1));
        $resTime  = max(1, (int) ($r['resolution_time'] ?? 1));
        $grace    = max(0, (int) ($r['grace_period'] ?? 0));
        $setup    = $this->s($r['setup_charge'] ?? '0');
        $conn     = $this->s($r['connection_charge'] ?? '0');
        $minCharge= isset($r['minimal_charge']) && $r['minimal_charge'] !== null ? $this->s($r['minimal_charge']) : null;

        if ($billsec <= 0) {
            return ['0', $this->round($setup)]; // setup only on a 0-duration attempt
        }
        if ($grace > 0 && $billsec <= $grace) {
            return ['0', '0.000000']; // within grace = free
        }

        // pulse: first block, then whole resolution_time steps
        if ($billsec <= $minTime) {
            $billed = $minTime;
        } else {
            $steps  = (int) ceil(($billsec - $minTime) / $resTime);
            $billed = $minTime + $steps * $resTime;
        }

        // per-minute cost = rate * billed/60
        $usage = bcmul($rate, bcdiv((string) $billed, '60', 8), self::SCALE);
        $cost  = bcadd(bcadd($setup, $conn, self::SCALE), $usage, self::SCALE);

        if ($minCharge !== null && bccomp($cost, $minCharge, self::SCALE) < 0) {
            $cost = $minCharge;
        }

        return [(string) $billed, $this->round($cost)];
    }

    /**
     * Rate a completed call and debit the account, idempotently.
     *
     * @param array $cdr keys: call_uuid, account_id, direction, source_number,
     *                   destination_number, carrier_id, billsec
     * @return RatedCdr the (new or pre-existing) rated row
     */
    public function rateAndDebit(array $cdr): RatedCdr
    {
        $uuid = (string) $cdr['call_uuid'];

        // idempotency fast path — already rated; also drop any lingering hold.
        $existing = RatedCdr::where('call_uuid', $uuid)->first();
        if ($existing) {
            CreditHold::where('call_uuid', $uuid)->delete();
            return $existing;
        }

        $accountId   = (string) $cdr['account_id'];
        $billsec     = max(0, (int) ($cdr['billsec'] ?? 0));
        $destination = (string) ($cdr['destination_number'] ?? '');
        $direction   = ($cdr['direction'] ?? 'outbound') === 'inbound' ? 'inbound' : 'outbound';

        // resolve the customer's ratecard + the longest-prefix rate for the destination
        [$ratecardId, $rateRow] = $this->resolveRate($accountId, $destination, $direction);
        [$billed, $cost] = $rateRow
            ? $this->computeCost($billsec, $rateRow)
            : ['0', '0.000000']; // no matching rate -> zero-rated, flagged by null ratecard

        // One transaction on the 'switch' connection. RatedCdr is now bound to
        // that same connection (qualified switchcdr table), so the idempotency
        // guard, the balance debit, the debit ledger row, and the hold release
        // all commit or roll back together (F1 fix).
        //
        // mod_xml_cdr posts one CDR per leg and both legs carry the same
        // cc_call_key, so the two POSTs land in the same millisecond. Both can
        // clear the fast-path SELECT above before either INSERTs — the UNIQUE key
        // is what actually prevents the double-bill. Catch that collision and
        // return the row the winner wrote, so the loser is a successful no-op
        // instead of a 500 that makes FreeSWITCH retry the CDR forever.
        try {
            return $this->insertAndDebit($uuid, $accountId, $direction, $cdr, $destination, $ratecardId, $rateRow, $billsec, $billed, $cost);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $winner = RatedCdr::where('call_uuid', $uuid)->first();
            if (! $winner) {
                throw $e; // a different unique key blew up — do not swallow it
            }
            CreditHold::where('call_uuid', $uuid)->delete();
            return $winner;
        }
    }

    /**
     * The rate-and-debit transaction itself. Split out so the caller can retry /
     * absorb a UNIQUE(call_uuid) collision without duplicating the maths.
     */
    private function insertAndDebit(
        string $uuid, string $accountId, string $direction, array $cdr, string $destination,
        ?string $ratecardId, ?array $rateRow, int $billsec, string $billed, string $cost
    ): RatedCdr {
        return DB::connection('switch')->transaction(function () use (
            $uuid, $accountId, $direction, $cdr, $destination, $ratecardId, $rateRow, $billsec, $billed, $cost
        ) {
            // write the rated CDR first — UNIQUE(call_uuid) is the idempotency guard
            $rated = RatedCdr::create([
                'call_uuid'          => $uuid,
                'account_id'         => $accountId,
                'direction'          => $direction,
                'source_number'      => $cdr['source_number'] ?? null,
                'destination_number' => $destination,
                'carrier_id'         => $cdr['carrier_id'] ?? null,
                'ratecard_id'        => $ratecardId,
                'prefix'             => $rateRow['prefix'] ?? null,
                'billsec'            => $billsec,
                'billed_seconds'     => (int) $billed,
                'rate'               => $rateRow['rate'] ?? 0,
                'cost'               => $cost,
                'currency_id'        => $rateRow['currency_id'] ?? null,
                'rated_at'           => now(),
            ]);

            if (bccomp($cost, '0', self::SCALE) > 0) {
                $bal = CustomerBalance::where('account_id', $accountId)->lockForUpdate()->first();
                if ($bal) {
                    // prepaid balance floors at 0 (never negative from a single call)
                    $before = $this->s($bal->balance);
                    $after  = bcsub($before, $cost, self::SCALE);
                    if (bccomp($after, '0', self::SCALE) < 0) {
                        $after = '0.000000';
                    }
                    $bal->balance = $after;
                    $bal->save();

                    // audit row so the running balance is reconstructable (M finding)
                    BalanceLedger::create([
                        'idempotency_key' => 'debit:' . $uuid,
                        'account_id'      => $accountId,
                        'kind'            => 'debit',
                        'amount'          => $cost,
                        'balance_before'  => $before,
                        'balance_after'   => $after,
                        'actor'           => 'switch',
                        'notes'           => 'call ' . $uuid,
                        'created_at'      => now(),
                    ]);
                }
            }

            // release the prepaid reservation for this call
            CreditHold::where('call_uuid', $uuid)->delete();

            return $rated;
        });
    }

    /**
     * Find the account's OUTGOING/INCOMING ratecard (via its tariff) and the
     * longest-prefix rate row matching the destination.
     * @return array{0: ?string, 1: ?array}  [ratecard_id, rateRow|null]
     */
    private function resolveRate(string $accountId, string $destination, string $direction): array
    {
        $vm = CustomerVoipMinute::where('account_id', $accountId)->where('status', '1')->first();
        if (! $vm) {
            return [null, null];
        }

        $for = $direction === 'inbound' ? 'INCOMING' : 'OUTGOING';
        $map = TariffRatecardMap::where('tariff_id', $vm->tariff_id)
            ->where('ratecard_for', $for)
            ->where('status', '1')
            ->orderBy('priority')
            ->first();
        if (! $map) {
            return [null, null];
        }

        // longest-prefix match against customer_rates for this ratecard
        $row = DB::connection('switch')->table('customer_rates')
            ->where('ratecard_id', $map->ratecard_id)
            ->where('rates_status', '1')
            ->whereRaw('? LIKE CONCAT(prefix, "%")', [$destination])
            ->orderByRaw('CHAR_LENGTH(prefix) DESC')
            ->first();

        if (! $row) {
            return [$map->ratecard_id, null];
        }

        return [$map->ratecard_id, (array) $row];
    }

    private function s(?string $v): string
    {
        return bcadd(($v === null || $v === '') ? '0' : (string) $v, '0', self::SCALE);
    }

    private function round(string $v): string
    {
        return bcadd($v, '0', self::SCALE);
    }
}
