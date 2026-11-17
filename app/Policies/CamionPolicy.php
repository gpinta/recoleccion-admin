<?php

namespace App\Policies;

use App\Ldap\User;
use App\Models\Camion;

class CamionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->puedeLeerBackend();
    }

    public function view(User $user, Camion $camion): bool
    {
        return $user->puedeLeerBackend();
    }

    public function create(User $user): bool
    {
        return $user->esBackendAdmin();
    }

    public function assignMunicipio(User $user, Camion $camion): bool
    {
        return $user->puedeOperarBackend();
    }

    public function update(User $user, Camion $camion): bool
    {
        return $user->esBackendAdmin();
    }

    public function delete(User $user, Camion $camion): bool
    {
        return $user->esBackendAdmin();
    }
}
