<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * OV500 `customer_sip_account` — a SIP endpoint (extension/trunk) for a customer.
 *
 * `secret` is the SIP digest password. Unlike a portal login (which we bcrypt),
 * a SIP secret is a shared secret the media layer (FreeSWITCH) must be able to
 * present, so it is inherently recoverable and stored as the switch expects.
 * We keep it out of serialisation ($hidden) and mask it in the UI; we never log it.
 */
class SipAccount extends Model
{
    protected $connection = 'switch';
    protected $table = 'customer_sip_account';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'username', 'secret', 'ipaddress', 'status', 'account_id', 'sip_cc', 'sip_cps',
        'ipauthfrom', 'extension_no', 'voicemail_enabled', 'voicemail', 'display_name',
        'caller_id', 'cli_prefer', 'codecs', 'moh_sound', 'name', 'email_address',
        'phone_number', 'ring_timeout', 'call_recording', 'dnd',
        'call_forward_all', 'cfall_destination_type', 'cfall_destination',
        'created_by', 'created_by_account_id', 'updated_by', 'created_dt', 'updated_dt',
        'user_type', 'extension_id',
    ];

    protected $hidden = ['secret'];

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    public function headers(): HasMany
    {
        return $this->hasMany(EndpointHeader::class, 'sip_username', 'username');
    }
}
