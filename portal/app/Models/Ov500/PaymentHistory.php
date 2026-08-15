<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/** OV500 `payment_history`. amount is DECIMAL(12,6) (exact). */
class PaymentHistory extends Model
{
    protected $connection = 'switch';
    protected $table = 'payment_history';
    protected $primaryKey = 'payment_id';
    public $timestamps = false;

    protected $fillable = [
        'account_id', 'payment_option_id', 'payment_collection_id', 'amount',
        'paid_on', 'notes', 'transaction_id', 'created_by', 'create_dt',
        // NOT NULL with no default in the OV500 schema; must be set under STRICT mode
        'file_name', 'other_data', 'invoice_data',
    ];
}
