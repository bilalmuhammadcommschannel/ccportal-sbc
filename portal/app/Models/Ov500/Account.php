<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * OV500 `account` row — the tenancy anchor. Every customer/reseller is an
 * account; parent_account_id forms the reseller tree. Display name lives on the
 * `customers` / `resellers` tables, joined here.
 */
class Account extends Model
{
    protected $connection = 'switch';
    protected $table = 'account';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = ['id']; // reads only in the money slice; no mass-assign writes here

    public function getRouteKeyName(): string
    {
        return 'account_id';
    }

    public function balance(): HasOne
    {
        return $this->hasOne(CustomerBalance::class, 'account_id', 'account_id');
    }

    public function customerRow(): HasOne
    {
        return $this->hasOne(\App\Models\Ov500\CustomerProfile::class, 'account_id', 'account_id');
    }

    public function resellerRow(): HasOne
    {
        return $this->hasOne(\App\Models\Ov500\ResellerProfile::class, 'account_id', 'account_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $name = $this->customerRow->company_name
            ?? $this->customerRow->name
            ?? $this->resellerRow->company_name
            ?? null;

        return $name ? "{$name} ({$this->account_id})" : $this->account_id;
    }

    /** Human label for the OV500 status_id code shown in the UI. */
    public function getStatusLabelAttribute(): string
    {
        return match ((string) $this->status_id) {
            '1'  => 'Active',
            '0'  => 'Inactive',
            '-3' => 'Closed',
            default => 'Unknown (' . $this->status_id . ')',
        };
    }

    /** CSS pill class: green only when active. */
    public function getStatusPillAttribute(): string
    {
        return (string) $this->status_id === '1' ? 'on' : 'off';
    }
}
