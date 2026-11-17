<?php

namespace App\Policies;

use App\Ldap\User;
use App\Models\Denuncia;

class DenunciaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->puedeLeerBackend();
    }

    public function view(User $user, Denuncia $denuncia): bool
    {
        return $user->puedeLeerBackend();
    }

    public function updateEstado(User $user, Denuncia $denuncia): bool
    {
        return $user->puedeOperarBackend()
            && (
                $user->esBackendAdmin()
                || !$denuncia->estadoEnum()->esTerminal()
            );
    }

    public function asignar(User $user, Denuncia $denuncia): bool
    {
        return $user->puedeOperarBackend()
            && (
                $user->esBackendAdmin()
                || !$denuncia->estadoEnum()->esTerminal()
            );
    }

    public function desasignar(User $user, Denuncia $denuncia): bool
    {
        return $user->puedeOperarBackend()
            && (
                $user->esBackendAdmin()
                || !$denuncia->estadoEnum()->esTerminal()
            );
    }

    public function verificarResolucion(User $user, Denuncia $denuncia): bool
    {
        return $user->puedeOperarBackend();
    }

    public function verFoto(User $user, Denuncia $denuncia): bool
    {
        return $user->puedeLeerBackend();
    }

    public function create(User $user): bool
    {
        /*
         * Las denuncias se originan únicamente desde
         * la Aplicación Ciudadana.
         */
        return false;
    }

    public function update(User $user, Denuncia $denuncia): bool
    {
        return $user->esBackendAdmin();
    }

    public function delete(User $user, Denuncia $denuncia): bool
    {
        return $user->esBackendAdmin();
    }
}
