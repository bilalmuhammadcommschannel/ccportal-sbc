<?php

namespace App\Console\Commands;

use App\Models\Ov500\CreditHold;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Releases prepaid credit reservations whose call never produced a CDR (switch
 * crash, lost CDR post, abandoned setup). Without this a leaked hold suppresses
 * a customer's available credit until the TTL lapses. The TTL is the safety net;
 * this is the active cleanup.
 */
class SweepCreditHolds extends Command
{
    protected $signature = 'cc:sweep-holds {--hours= : override the TTL in hours}';
    protected $description = 'Release prepaid credit holds older than the configured TTL';

    public function handle(): int
    {
        $hours = (int) ($this->option('hours') ?: config('switch.hold_ttl_hours', 6));
        $cutoff = now()->subHours($hours);

        $stale = CreditHold::where('created_at', '<', $cutoff)->get();
        if ($stale->isEmpty()) {
            $this->info('No stale credit holds.');
            return self::SUCCESS;
        }

        foreach ($stale as $h) {
            Log::channel('auth')->warning(
                "credit hold released as orphaned: call={$h->call_uuid} account={$h->account_id} amount={$h->hold_amount}"
            );
        }
        $n = CreditHold::where('created_at', '<', $cutoff)->delete();
        $this->info("Released {$n} orphaned credit hold(s) older than {$hours}h.");

        return self::SUCCESS;
    }
}
