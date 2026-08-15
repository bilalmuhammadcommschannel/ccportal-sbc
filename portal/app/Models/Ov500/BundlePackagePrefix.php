<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/** OV500 `bundle_package_prefixes` — a dialled prefix mapped to a bundle tier (1/2/3). */
class BundlePackagePrefix extends Model
{
    protected $connection = 'switch';
    protected $table = 'bundle_package_prefixes';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['bundle_package_id', 'bundle_id', 'prefix'];
}
