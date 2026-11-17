<?php

namespace App\Ldap;

use App\Enums\AdminRole;
use LdapRecord\Models\ActiveDirectory\User as ActiveDirectoryUser;

class User extends ActiveDirectoryUser
{
    protected $authAttribute = 'samaccountname';

    private bool $adminRoleResolved = false;

    private ?AdminRole $resolvedAdminRole = null;

    /**
     * Resuelve el rol funcional del Backend Administrativo
     * a partir de los grupos de Active Directory.
     *
     * Prioridad:
     * admin > operator > reader.
     *
     * La prioridad evita resultados ambiguos si un usuario
     * pertenece accidentalmente a más de un grupo.
     */
    public function adminRole(): ?AdminRole
    {
        if ($this->adminRoleResolved) {
            return $this->resolvedAdminRole;
        }

        $this->adminRoleResolved = true;

        $gruposConfigurados =
            config('admin.ldap_groups');

        if (!is_array($gruposConfigurados)) {
            return null;
        }

        $gruposUsuario =
            $this->groups()
                ->get()
                ->map(
                    fn ($grupo) =>
                        $grupo->getDn()
                )
                ->filter(
                    fn ($dn) =>
                        is_string($dn)
                        && trim($dn) !== ''
                );

        $roles = [
            [
                AdminRole::ADMIN,
                $gruposConfigurados['admins']
                ?? null,
            ],
            [
                AdminRole::OPERATOR,
                $gruposConfigurados['operators']
                ?? null,
            ],
            [
                AdminRole::READER,
                $gruposConfigurados['readers']
                ?? null,
            ],
        ];

        foreach ($roles as [$role, $dnConfigurado]) {
            if (
                !is_string($dnConfigurado)
                || trim($dnConfigurado) === ''
            ) {
                continue;
            }

            $pertenece =
                $gruposUsuario->contains(
                    fn (string $dnUsuario): bool =>
                        strcasecmp(
                            $dnUsuario,
                            $dnConfigurado
                        ) === 0
                );

            if ($pertenece) {
                return $this->resolvedAdminRole =
                    $role;
            }
        }

        return null;
    }

    public function puedeLeerBackend(): bool
    {
        return $this->adminRole() !== null;
    }

    public function puedeOperarBackend(): bool
    {
        return $this
            ->adminRole()
            ?->puedeOperar()
            ?? false;
    }

    public function esBackendAdmin(): bool
    {
        return $this
            ->adminRole()
            ?->esAdmin()
            ?? false;
    }
}
