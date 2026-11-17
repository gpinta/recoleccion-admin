<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Mostrar formulario de inicio de sesión administrativo.
     */
    public function showLogin()
    {
        return view(
            'admin.auth.login'
        );
    }

    /**
     * Procesar autenticación contra Active Directory.
     */
    public function login(
        LoginRequest $request
    ) {
        $validated =
            $request->validated();

        /*
         |--------------------------------------------------------------------------
         | Credenciales LDAP del usuario
         |--------------------------------------------------------------------------
         |
         | El provider LDAP se encuentra configurado en:
         |
         | config/auth.php
         |
         | El usuario escribe su identidad personal.
         |
         | Ejemplo:
         |
         | nombre.apellido
         |
         | La contraseña es validada directamente contra
         | Active Directory mediante LdapRecord.
         |
         */

        $credentials = [

            'samaccountname' =>
                $validated['username'],

            'password' =>
                $validated['password'],

        ];


        /*
         |--------------------------------------------------------------------------
         | Autenticación
         |--------------------------------------------------------------------------
         |
         | No diferenciamos externamente entre:
         |
         | - usuario inexistente;
         | - contraseña incorrecta;
         | - usuario no autorizado.
         |
         | Esto reduce información útil para enumeración
         | de cuentas de Active Directory.
         |
         */

        if (!Auth::attempt($credentials)) {

            $this->throwAuthenticationError();

        }


        $user =
            Auth::user();


        /*
         |--------------------------------------------------------------------------
         | Obtener grupos autorizados
         |--------------------------------------------------------------------------
         */

        $gruposConfigurados =
            config(
                'admin.ldap_groups'
            );


        /*
         |--------------------------------------------------------------------------
         | Fail closed
         |--------------------------------------------------------------------------
         |
         | Si no existe configuración válida de grupos LDAP,
         | ningún usuario debe obtener acceso administrativo.
         |
         */

        if (
            !is_array(
                $gruposConfigurados
            )
        ) {

            $this->logoutFailedAuthorization(
                $request
            );

            abort(
                500,
                'La configuración de acceso administrativo no está disponible.'
            );

        }


        /*
         |--------------------------------------------------------------------------
         | Normalizar DNs configurados
         |--------------------------------------------------------------------------
         |
         | Eliminamos valores:
         |
         | - null;
         | - vacíos;
         | - que no sean string.
         |
         */

        $gruposPermitidos =
            collect(
                $gruposConfigurados
            )
                ->filter(
                    function (
                        mixed $grupo
                    ): bool {
                        return is_string(
                            $grupo
                        )
                            && trim(
                                $grupo
                            ) !== '';
                    }
                )
                ->values();


        /*
         * Si ambos grupos están ausentes o vacíos,
         * fallamos cerrado.
         */
        if (
            $gruposPermitidos
                ->isEmpty()
        ) {

            $this->logoutFailedAuthorization(
                $request
            );

            abort(
                500,
                'No existen grupos LDAP autorizados configurados.'
            );

        }


        /*
         |--------------------------------------------------------------------------
         | Obtener grupos del usuario LDAP
         |--------------------------------------------------------------------------
         */

        $gruposUsuario =
            $user
                ->groups()
                ->get();


        /*
         |--------------------------------------------------------------------------
         | Comprobar autorización
         |--------------------------------------------------------------------------
         |
         | El usuario puede ingresar si pertenece al menos a uno:
         |
         | GG_REC_Backend_Operators
         |
         | o
         |
         | GG_REC_Backend_Admins
         |
         | La comparación de DN no distingue mayúsculas de minúsculas.
         |
         */

        $perteneceAGrupoPermitido =
            $gruposUsuario->contains(
                function (
                    $grupoUsuario
                ) use (
                    $gruposPermitidos
                ): bool {

                    $dnUsuario =
                        $grupoUsuario
                            ->getDn();

                    return $gruposPermitidos
                        ->contains(
                            function (
                                string $dnPermitido
                            ) use (
                                $dnUsuario
                            ): bool {

                                return strcasecmp(
                                    $dnUsuario,
                                    $dnPermitido
                                ) === 0;

                            }
                        );

                }
            );


        /*
         |--------------------------------------------------------------------------
         | Usuario autenticado pero no autorizado
         |--------------------------------------------------------------------------
         */

        if (
            !$perteneceAGrupoPermitido
        ) {

            $this->logoutFailedAuthorization(
                $request
            );

            /*
             * Utilizamos deliberadamente el mismo mensaje
             * utilizado para credenciales incorrectas.
             */

            $this->throwAuthenticationError();

        }


        /*
         |--------------------------------------------------------------------------
         | Sesión válida
         |--------------------------------------------------------------------------
         |
         | Regeneramos la sesión después de autenticar para
         | prevenir session fixation.
         |
         */

        $request
            ->session()
            ->regenerate();


        return redirect()
            ->intended(
                route(
                    'admin.dashboard'
                )
            );
    }


    /**
     * Cerrar sesión.
     */
    public function logout(
        Request $request
    ) {
        Auth::logout();

        $request
            ->session()
            ->invalidate();

        $request
            ->session()
            ->regenerateToken();


        return redirect()
            ->route(
                'admin.login'
            );
    }


    /**
     * Elimina completamente una sesión iniciada cuando
     * el usuario LDAP se autentica correctamente pero no
     * posee autorización para ingresar al Backend.
     */
    private function logoutFailedAuthorization(
        Request $request
    ): void {
        Auth::logout();

        $request
            ->session()
            ->invalidate();

        $request
            ->session()
            ->regenerateToken();
    }


    /**
     * Respuesta deliberadamente genérica ante fallos
     * de autenticación o autorización.
     */
    private function throwAuthenticationError(): never
    {
        throw ValidationException::withMessages([
            'username' => [
                'No fue posible iniciar sesión con las credenciales proporcionadas.',
            ],
        ]);
    }
}