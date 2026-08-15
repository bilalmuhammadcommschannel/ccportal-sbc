<?php

namespace App\Policies;

use App\Models\Ov500\SipAccount;
use App\Models\User;

/** Default-deny. Endpoint belongs to an account; reseller/customer scoped by subtree. */
class SipAccountPolicy
{
    public function viewAny(User $u): bool { return in_array($u->role, ['admin','reseller','customer'], true); }
    public function view(User $u, SipAccount $s): bool { return $this->owns($u, $s->account_id); }
    public function create(User $u): bool { return in_array($u->role, ['admin','reseller'], true); }
    public function update(User $u, SipAccount $s): bool
    {
        return in_array($u->role, ['admin','reseller'], true) && $this->owns($u, $s->account_id);
    }

    private function owns(User $u, ?string $accountId): bool
    {
        if ($u->isAdmin()) return true;
        return $accountId !== null && in_array($accountId, $u->accessibleAccountIds(), true);
    }
}
