<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/**
 * CommsChannel rated CDR. Physically in the switchcdr DB, but bound to the
 * 'switch' CONNECTION and referenced as a qualified cross-database table so it
 * shares one PDO — and therefore one transaction — with customer_balance and
 * cc_balance_ledger. This is the F1 fix: the idempotency guard (UNIQUE call_uuid)
 * and the balance debit must commit or roll back together. Both DBs live on the
 * same MySQL server and the portal user has DML on both.
 */
class RatedCdr extends Model
{
    protected $connection = 'switch';
    protected $table = 'switchcdr.cc_rated_cdr';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'call_uuid', 'account_id', 'direction', 'source_number', 'destination_number',
        'carrier_id', 'ratecard_id', 'prefix', 'billsec', 'billed_seconds',
        'rate', 'cost', 'currency_id', 'rated_at',
    ];
}
