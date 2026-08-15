<?php

namespace App\Policies;

use App\Models\Ov500\Carrier;
use App\Models\User;

/**
 * Default-deny authorization for carriers.
 *
 * Carriers are a platform-level resource in slice 1 — only the admin role may
 * touch them. The methods are written against the user AND the model so that
 * when carriers become reseller-scoped later, the ownership check is added in
 * one place (compare $carrier->account_id against $user->accessibleAccountIds())
 * rather than sprinkled through the controller.
 *
 * Every method returns false unless a rule explicitly grants — there is no
 * catch-all allow, and no Gate::before override that could silently open it.
 */
class CarrierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Carrier $carrier): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Carrier $carrier): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Carrier $carrier): bool
    {
        return $user->isAdmin();
    }
}
