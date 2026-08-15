<?php

namespace App\Policies;

use App\Models\Ov500\Ratecard;
use App\Models\User;

/** Default-deny. Governs the ratecard AND its rate rows (editing rates = updating the card). */
class RatecardPolicy
{
    public function viewAny(User $u): bool { return $u->isAdmin(); }
    public function view(User $u, Ratecard $r): bool { return $u->isAdmin(); }
    public function create(User $u): bool { return $u->isAdmin(); }
    public function update(User $u, Ratecard $r): bool { return $u->isAdmin(); }
}
