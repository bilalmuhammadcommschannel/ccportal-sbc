<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/** OV500 `bundle_account` — a bundle_package assigned to an account. */
class BundleAccount extends Model
{
    protected $connection = 'switch';
    protected $table = 'bundle_account';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'bundle_package_id', 'account_id', 'assign_dt', 'account_bundle_key',
        'bundle_package_desc', 'lastbill_execute_date', 'lastbilldate',
        'created_by', 'updated_by', 'created_dt', 'updated_dt',
    ];
}
