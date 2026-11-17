<?php

namespace App\Policies;

use App\Ldap\User;
use App\Models\Municipio;

class MunicipioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->puedeLeerBackend();
    }

    public function create(User $user): bool
    {
        return $user->esBackendAdmin();
    }

    public function update(User $user, Municipio $municipio): bool
    {
        return $user->esBackendAdmin();
    }

    public function delete(User $user, Municipio $municipio): bool
    {
        return $user->esBackendAdmin();
    }
}
