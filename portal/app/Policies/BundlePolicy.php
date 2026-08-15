<?php

namespace App\Policies;

use App\Models\Ov500\BundlePackage;
use App\Models\User;

/** Default-deny. Bundle packages are platform pricing config — admin-managed. */
class BundlePolicy
{
    public function viewAny(User $u): bool { return $u->isAdmin(); }
    public function view(User $u, BundlePackage $b): bool { return $u->isAdmin(); }
    public function create(User $u): bool { return $u->isAdmin(); }
    public function update(User $u, BundlePackage $b): bool { return $u->isAdmin(); }
}
