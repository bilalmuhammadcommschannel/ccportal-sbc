<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * OV500 `carrier` row. Lives in the `switch` database (DML-only connection).
 *
 * `id` is the numeric surrogate PK; `carrier_id` is the varchar business key the
 * rest of the OV500 schema and the Kamailio/FreeSWITCH config join on.
 *
 * Every attribute is assigned through $fillable + validated request data — there
 * is no raw SQL anywhere in this model. This is the deliberate inverse of the
 * OV500 portal, where carrier writes were string-concatenated into UPDATE ... WHERE.
 */
class Carrier extends Model
{
    protected $connection = 'switch';
    protected $table = 'carrier';
    protected $primaryKey = 'id';
    public $timestamps = false; // schema uses created_dt / updated_dt, set explicitly

    protected $fillable = [
        'carrier_id', 'carrier_name', 'tariff_id', 'carrier_type', 'carrier_status',
        'carrier_cps', 'carrier_cc', 'carrier_currency_id', 'provider_id',
        'carrier_progress_timeout', 'carrier_ring_timeout', 'cli_prefer',
        'carrier_codecs', 'gateway_withmedia', 'tax1', 'tax2', 'tax3', 'tax_type',
        'dp', 'vat_flag', 'tax_number', 'account_id',
        'created_by', 'updated_by', 'created_dt', 'updated_dt',
    ];

    protected function casts(): array
    {
        return [
            'carrier_status' => 'integer',
            'carrier_cps'    => 'integer',
            'carrier_cc'     => 'integer',
            'created_dt'     => 'datetime',
            'updated_dt'     => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'carrier_id';
    }

    public function ips(): HasMany
    {
        return $this->hasMany(CarrierIp::class, 'carrier_id', 'carrier_id');
    }

    public function prefixes(): HasMany
    {
        return $this->hasMany(CarrierPrefix::class, 'carrier_id', 'carrier_id');
    }
}
