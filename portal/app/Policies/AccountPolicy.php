<?php

namespace App\Policies;

use App\Models\Ov500\Account;
use App\Models\User;

/**
 * Default-deny tenancy authorization for accounts and their balances.
 *
 * admin     — any account.
 * reseller  — only accounts in their own subtree (accessibleAccountIds()).
 * customer  — may view their OWN balance; may NOT top up (only an operator credits).
 *
 * The ownership test is the SINGLE source of truth for "can this user touch this
 * account", used by every balance/customer/DID action. OV500 scattered (and
 * frequently omitted) this check per-controller; here it is one method.
 */
class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'reseller'], true);
    }

    public function view(User $user, Account $account): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->role === 'customer') {
            return $user->account_id === $account->account_id;
        }
        return $this->owns($user, $account);
    }

    public function create(User $user): bool
    {
        // admin creates any account; reseller creates sub-accounts under itself
        return in_array($user->role, ['admin', 'reseller'], true);
    }

    public function update(User $user, Account $account): bool
    {
        return $user->isAdmin() || $this->owns($user, $account);
    }

    public function delete(User $user, Account $account): bool
    {
        // Deleting or archiving a customer is a platform action — admin only.
        // Resellers suspend via the status field on the edit screen instead.
        return $user->isAdmin();
    }

    public function topUp(User $user, Account $account): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        // A reseller may credit an account in their subtree, but never their own
        // (self-crediting is not a top-up). Customers can never credit.
        return $user->role === 'reseller'
            && $account->account_id !== $user->account_id
            && $this->owns($user, $account);
    }

    private function owns(User $user, Account $account): bool
    {
        return in_array($account->account_id, $user->accessibleAccountIds(), true);
    }
}
