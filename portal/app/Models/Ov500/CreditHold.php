<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/**
 * cc_credit_holds — a prepaid credit reservation taken at call SETUP and released
 * at the CDR. Available credit = balance - SUM(active holds), so concurrent calls
 * cannot each spend the whole balance (F2 fix). Holds older than the expiry window
 * are ignored in the sum, so a call that never produces a CDR self-heals.
 */
class CreditHold extends Model
{
    protected $connection = 'switch';
    protected $table = 'cc_credit_holds';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['call_uuid', 'account_id', 'hold_amount', 'created_at'];
}
