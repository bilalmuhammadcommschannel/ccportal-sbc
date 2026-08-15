<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/** OV500 `customer_voipminuts` — links an account to its assigned tariff. */
class CustomerVoipMinute extends Model
{
    protected $connection = 'switch';
    protected $table = 'customer_voipminuts';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'customer_voipminute_id', 'account_id', 'billingcode', 'account_type',
        'tariff_id', 'status', 'created_by', 'updated_by', 'created_dt', 'updated_dt',
    ];
}
