<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/** OV500 `did_dst` — where an inbound DID call is routed (primary + failover). */
class DidDestination extends Model
{
    protected $connection = 'switch';
    protected $table = 'did_dst';
    protected $primaryKey = 'did_dst_id';
    public $timestamps = false;

    protected $fillable = [
        'did_number', 'account_id', 'dst_type', 'dst_destination',
        'dst_type2', 'dst_destination2', 'create_date',
    ];
}
