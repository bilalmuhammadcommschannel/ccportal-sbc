<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/** OV500 `customers` — profile + billing terms for a CUSTOMER account. */
class CustomerProfile extends Model
{
    protected $connection = 'switch';
    protected $table = 'customers';
    protected $primaryKey = 'customer_id';
    public $timestamps = false;

    protected $fillable = [
        'account_id', 'company_name', 'contact_name', 'name', 'address',
        'country_id', 'state_code_id', 'phone', 'emailaddress',
        'billing_type', 'billing_cycle', 'payment_terms', 'next_billing_date',
        'pincode', 'created_by', 'updated_by', 'created_dt',
    ];
}
