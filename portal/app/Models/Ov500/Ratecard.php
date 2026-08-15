<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * OV500 `ratecard` — a named set of prefix rates, typed CARRIER|CUSTOMER and
 * directional INCOMING|OUTGOING. Its rate rows live in carrier_rates (CARRIER)
 * or customer_rates (CUSTOMER), keyed by ratecard_id.
 */
class Ratecard extends Model
{
    protected $connection = 'switch';
    protected $table = 'ratecard';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'ratecard_id', 'ratecard_name', 'ratecard_type', 'account_id',
        'ratecard_currency_id', 'ratecard_for',
        'created_by', 'updated_by', 'created_dt', 'updated_dt',
    ];

    public function getRouteKeyName(): string
    {
        return 'ratecard_id';
    }

    /** Fully-qualified rate model for this ratecard's type. */
    public function rateModelClass(): string
    {
        return $this->ratecard_type === 'CARRIER' ? CarrierRate::class : CustomerRate::class;
    }

    public function rates(): HasMany
    {
        return $this->hasMany($this->rateModelClass(), 'ratecard_id', 'ratecard_id');
    }
}
