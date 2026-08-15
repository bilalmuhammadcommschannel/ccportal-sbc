<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/**
 * OV500 `carrier_prefix` row — digit-manipulation rule for a carrier route.
 * maching_string is matched; remove_string is stripped; add_string is prepended.
 */
class CarrierPrefix extends Model
{
    protected $connection = 'switch';
    protected $table = 'carrier_prefix';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'carrier_id', 'maching_string', 'remove_string', 'add_string',
        'display_string', 'route', 'account_id',
        'created_by', 'updated_by', 'created_dt', 'updated_dt',
    ];
}
