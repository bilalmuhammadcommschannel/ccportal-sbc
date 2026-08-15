<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * OV500 `did` — a phone number in inventory. account_id = the end customer it is
 * assigned to; reseller1/2/3_account_id = the reseller chain above that customer
 * (3-level), each with its own last-billed date for monthly rental billing.
 */
class Did extends Model
{
    protected $connection = 'switch';
    protected $table = 'did';
    protected $primaryKey = 'did_id';
    public $timestamps = false;

    protected $fillable = [
        'did_number', 'did_status', 'carrier_id', 'account_id', 'assign_date',
        'reseller1_account_id', 'reseller1_assign_date',
        'reseller2_account_id', 'reseller2_assign_date',
        'reseller3_account_id', 'reseller3_assign_date',
        'create_date', 'channels', 'did_name', 'number_type',
        'lastbilldate', 'r1lastbilldate', 'r2lastbilldate', 'r3lastbilldate',
    ];

    public function getRouteKeyName(): string
    {
        return 'did_number';
    }

    public function destination(): HasOne
    {
        return $this->hasOne(DidDestination::class, 'did_number', 'did_number');
    }
}
