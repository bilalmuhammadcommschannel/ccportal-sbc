<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * OV500 `tariff` — the billing plan assigned to customer accounts (CUSTOMER) or
 * to carriers (CARRIER). A tariff maps to one or more ratecards (per direction +
 * time window) through tariff_ratecard_map. Bundle fields carry the flat-rate /
 * overflow model (bundle1/2/3 = MINUTE|COST tiers).
 */
class Tariff extends Model
{
    protected $connection = 'switch';
    protected $table = 'tariff';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'tariff_id', 'tariff_name', 'tariff_currency_id', 'tariff_status',
        'tariff_description', 'tariff_type', 'account_id',
        'package_option', 'monthly_charges', 'bundle_option',
        'bundle1_type', 'bundle1_value', 'bundle2_type', 'bundle2_value',
        'bundle3_type', 'bundle3_value',
        'created_by', 'updated_by', 'create_dt', 'update_dt',
    ];

    public function getRouteKeyName(): string
    {
        return 'tariff_id';
    }

    public function maps(): HasMany
    {
        return $this->hasMany(TariffRatecardMap::class, 'tariff_id', 'tariff_id');
    }
}
