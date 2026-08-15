<?php

namespace App\Models\Ov500;

use App\Models\Concerns\RateRow;
use Illuminate\Database\Eloquent\Model;

/** OV500 `customer_rates` — prefix rates for CUSTOMER ratecards. */
class CustomerRate extends Model
{
    use RateRow;

    protected $connection = 'switch';
    protected $table = 'customer_rates';
    protected $primaryKey = 'rate_id';
    public $timestamps = false;
    protected $fillable = [];
}
