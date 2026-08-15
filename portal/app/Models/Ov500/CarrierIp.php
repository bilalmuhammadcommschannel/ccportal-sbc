<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;

/**
 * OV500 `carrier_ips` row — one signalling endpoint for a carrier.
 *
 * auth_type = IP        -> carrier authenticates us by source IP (username/passwd unused)
 * auth_type = CUSTOMER  -> credential auth; username/passwd are the SIP trunk creds
 *
 * `passwd` is $hidden so it never leaks into JSON/array serialisation. In the
 * OV500 portal these credentials were echoed into logs and pages; here they are
 * write-through only and masked on display.
 */
class CarrierIp extends Model
{
    protected $connection = 'switch';
    protected $table = 'carrier_ips';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'carrier_ip_id', 'carrier_id', 'ipaddress_name', 'ipaddress',
        'load_share', 'priority', 'ip_status', 'auth_type', 'username', 'passwd',
        'account_id', 'created_by', 'updated_by', 'created_dt', 'updated_dt',
    ];

    protected $hidden = ['passwd'];

    protected function casts(): array
    {
        return [
            'load_share' => 'integer',
            'priority'   => 'integer',
        ];
    }
}
