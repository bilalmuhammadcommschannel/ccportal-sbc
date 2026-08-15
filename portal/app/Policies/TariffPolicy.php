<?php

namespace App\Policies;

use App\Models\Ov500\Tariff;
use App\Models\User;

/** Default-deny. Tariffs are platform pricing config — admin-managed in this slice. */
class TariffPolicy
{
    public function viewAny(User $u): bool { return $u->isAdmin(); }
    public function view(User $u, Tariff $t): bool { return $u->isAdmin(); }
    public function create(User $u): bool { return $u->isAdmin(); }
    public function update(User $u, Tariff $t): bool { return $u->isAdmin(); }
}
