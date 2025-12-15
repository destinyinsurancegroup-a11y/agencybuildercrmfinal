<?php

namespace App\Policies;

use App\Models\GideonSparringSession;
use App\Models\User;

class GideonSparringSessionPolicy
{
    public function view(User $user, GideonSparringSession $session): bool
    {
        return $session->isOwnedBy($user);
    }

    public function update(User $user, GideonSparringSession $session): bool
    {
        return $session->isOwnedBy($user);
    }
}
