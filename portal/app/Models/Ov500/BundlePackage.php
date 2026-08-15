<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * OV500 `bundle_package` — a flat-rate / bundle plan with up to three tiers.
 * Each tier (bundle1/2/3) is MINUTE (an inclusive-minutes allowance) or COST
 * (an inclusive spend allowance); bundle_package_prefixes says which dialled
 * prefixes draw from which tier. package_option/bundle_option toggle behaviour.
 */
class BundlePackage extends Model
{
    protected $connection = 'switch';
    protected $table = 'bundle_package';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'bundle_package_id', 'bundle_package_name', 'bundle_package_currency_id',
        'bundle_package_status', 'bundle_package_description',
        'package_option', 'monthly_charges', 'bundle_option',
        'bundle1_type', 'bundle1_value', 'bundle2_type', 'bundle2_value',
        'bundle3_type', 'bundle3_value',
        'account_id', 'created_by', 'updated_by', 'create_dt', 'update_dt',
    ];

    public function getRouteKeyName(): string
    {
        return 'bundle_package_id';
    }

    public function prefixes(): HasMany
    {
        return $this->hasMany(BundlePackagePrefix::class, 'bundle_package_id', 'bundle_package_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(BundleAccount::class, 'bundle_package_id', 'bundle_package_id');
    }
}
