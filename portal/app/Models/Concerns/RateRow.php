<?php

namespace App\Models\Concerns;

/**
 * Shared shape for customer_rates and carrier_rates (identical columns).
 *   minimal_time    = first billing pulse (e.g. 60 for 60/60, 1 for 1/1)
 *   resolution_time = subsequent pulse
 *   inclusive_channel + exclusive_per_channel_rental = per-channel flat pricing
 *   setup_charge / connection_charge / grace_period / minimal_charge = call charges
 */
trait RateRow
{
    public function initializeRateRow(): void
    {
        $this->fillable = array_merge($this->fillable ?? [], [
            'ratecard_id', 'prefix', 'destination', 'setup_charge', 'rental', 'rate',
            'connection_charge', 'minimal_time', 'resolution_time', 'grace_period',
            'rate_multiplier', 'rate_addition', 'rates_status',
            'exclusive_per_channel_rental', 'inclusive_channel', 'account_id',
            'minimal_charge', 'ani_prefix', 'updated_by', 'created_by',
            'create_dt', 'update_dt',
        ]);
    }
}
