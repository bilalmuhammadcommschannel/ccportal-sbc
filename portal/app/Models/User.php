<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'account_id', // OV500 switch.account.account_id this portal user is scoped to (null = platform-wide)
        'role',       // admin | reseller | customer
        'must_change_password',
        'password_changed_at',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed', // Laravel hashes on set; never stored in clear
            'must_change_password' => 'boolean',
            'is_active'            => 'boolean',
            'password_changed_at'  => 'datetime',
            'last_login_at'        => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isReseller(): bool
    {
        return $this->role === 'reseller';
    }

    /**
     * Account IDs this user may act within. Admin = all (null sentinel handled by
     * callers). Reseller = own account plus every descendant in the OV500 tree.
     * Customer = own account only. Used by tenant-scoped queries; carriers in
     * slice 1 are admin-only, but the mechanism is built here so later modules
     * inherit it rather than re-inventing per-query scoping (the OV500 failure).
     */
    public function accessibleAccountIds(): array
    {
        if ($this->isAdmin()) {
            return []; // empty = unrestricted; callers must treat [] as "no filter"
        }

        $ids = [$this->account_id];

        if ($this->isReseller() && $this->account_id) {
            // walk the account tree downward from this reseller
            $frontier = [$this->account_id];
            while ($frontier) {
                $children = \DB::connection('switch')
                    ->table('account')
                    ->whereIn('parent_account_id', $frontier)
                    ->pluck('account_id')
                    ->all();
                $children = array_values(array_diff($children, $ids));
                if (!$children) {
                    break;
                }
                $ids = array_merge($ids, $children);
                $frontier = $children;
            }
        }

        return array_values(array_filter(array_unique($ids)));
    }
}
