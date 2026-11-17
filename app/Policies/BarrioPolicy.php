<?php

namespace App\Policies;

use App\Ldap\User;
use App\Models\Barrio;

class BarrioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->puedeLeerBackend();
    }

    public function create(User $user): bool
    {
        return $user->esBackendAdmin();
    }

    public function assignMunicipio(User $user, Barrio $barrio): bool
    {
        return $user->puedeOperarBackend();
    }

    public function update(User $user, Barrio $barrio): bool
    {
        return $user->esBackendAdmin();
    }

    public function delete(User $user, Barrio $barrio): bool
    {
        return $user->esBackendAdmin();
    }
}
