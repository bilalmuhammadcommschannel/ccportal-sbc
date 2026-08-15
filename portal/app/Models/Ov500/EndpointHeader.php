<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/**
 * cc_endpoint_headers — custom SIP headers to inject for an endpoint. Not part of
 * the stock OV500 schema; added by CommsChannel. The switch dialplan (api/) reads
 * these and emits sip_h_<name> variables on the relevant leg (in/out/both).
 */
class EndpointHeader extends Model
{
    protected $connection = 'switch';
    protected $table = 'cc_endpoint_headers';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'sip_username', 'account_id', 'header_name', 'header_value',
        'direction', 'created_by', 'created_at', 'updated_at',
    ];
}
