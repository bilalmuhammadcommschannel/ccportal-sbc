<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/** OV500 `resellers` — profile for a RESELLER account. */
class ResellerProfile extends Model
{
    protected $connection = 'switch';
    protected $table = 'resellers';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'account_id', 'company_name', 'contact_name', 'address',
        'country_id', 'state_code_id', 'phone', 'emailaddress', 'pincode',
    ];
}
