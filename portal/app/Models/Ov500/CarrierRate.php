<?php

namespace App\Models\Ov500;

use App\Models\Concerns\RateRow;
use Illuminate\Database\Eloquent\Model;

/** OV500 `carrier_rates` — prefix rates for CARRIER ratecards. */
class CarrierRate extends Model
{
    use RateRow;

    protected $connection = 'switch';
    protected $table = 'carrier_rates';
    protected $primaryKey = 'rate_id';
    public $timestamps = false;
    protected $fillable = [];
}
