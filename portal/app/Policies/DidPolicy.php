<?php

namespace App\Policies;

use App\Models\Ov500\Did;
use App\Models\User;

/**
 * DID inventory is admin-owned; assignment reaches into the reseller/customer
 * tree. Reseller may view/assign DIDs already in their subtree; customer may
 * view their own. Creating/releasing inventory is admin-only.
 */
class DidPolicy
{
    public function viewAny(User $u): bool { return in_array($u->role, ['admin','reseller'], true); }

    public function view(User $u, Did $d): bool
    {
        if ($u->isAdmin()) return true;
        $ids = $u->accessibleAccountIds();
        return in_array($d->account_id, $ids, true)
            || in_array($d->reseller1_account_id, $ids, true)
            || in_array($d->reseller2_account_id, $ids, true)
            || in_array($d->reseller3_account_id, $ids, true);
    }

    public function create(User $u): bool { return $u->isAdmin(); }   // add inventory
    public function assign(User $u, Did $d): bool { return $u->isAdmin() || $this->view($u, $d); }
    public function release(User $u, Did $d): bool { return $u->isAdmin(); }
}
