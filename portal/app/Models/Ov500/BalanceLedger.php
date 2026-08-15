<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/**
 * cc_balance_ledger — CommsChannel idempotent top-up audit trail. Lives in the
 * `switch` DB so it shares a connection (and therefore a transaction) with
 * customer_balance and payment_history. The UNIQUE(idempotency_key) is the
 * hard guarantee against double-crediting under retry/double-submit/race.
 */
class BalanceLedger extends Model
{
    protected $connection = 'switch';
    protected $table = 'cc_balance_ledger';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'idempotency_key', 'account_id', 'kind', 'amount',
        'balance_before', 'balance_after', 'payment_history_id',
        'actor', 'notes', 'created_at',
    ];
}
