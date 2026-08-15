<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/**
 * OV500 `customer_balance`. `balance` is double(12,6) in the shipped schema —
 * FreeSWITCH/Kamailio read it, so we cannot retype the column here. All
 * arithmetic on it is done with bcmath strings (never PHP floats) and the exact
 * audit record lives in cc_balance_ledger (DECIMAL). Migrating this column to
 * DECIMAL is on the hardening backlog and must be coordinated with the switch.
 */
class CustomerBalance extends Model
{
    protected $connection = 'switch';
    protected $table = 'customer_balance';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'account_id', 'balance', 'credit_limit', 'maxcredit_limit', 'service_type',
    ];
}
