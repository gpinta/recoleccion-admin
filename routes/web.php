<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BarrioController;
use App\Http\Controllers\Admin\CamionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DenunciaController;
use App\Http\Controllers\Admin\FotoDenunciaController;
use App\Http\Controllers\Admin\FotoResolucionController;
use App\Http\Controllers\Admin\MunicipioController;
use App\Http\Middleware\EnsureBackendRole;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticación administrativa
|--------------------------------------------------------------------------
*/

Route::middleware('guest')
    ->group(function () {

        Route::get(
            '/admin/login',
            [
                AuthController::class,
                'showLogin',
            ]
        )->name(
            'admin.login'
        );

        /*
         * Máximo 5 intentos por minuto según
         * la estrategia de throttle estándar.
         */
        Route::post(
            '/admin/login',
            [
                AuthController::class,
                'login',
            ]
        )
            ->middleware(
                'throttle:5,1'
            )
            ->name(
                'admin.login.submit'
            );
    });

/*
|--------------------------------------------------------------------------
| Redirección raíz
|--------------------------------------------------------------------------
*/

Route::get(
    '/',
    function () {
        return auth()->check()
            ? redirect()
                ->route(
                    'admin.dashboard'
                )
            : redirect()
                ->route(
                    'admin.login'
                );
    }
);

/*
|--------------------------------------------------------------------------
| Backend autenticado
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    EnsureBackendRole::class,
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
         * Logout
         */
        Route::post(
            '/logout',
            [
                AuthController::class,
                'logout',
            ]
        )->name(
            'logout'
        );

        /*
         * Dashboard
         */
        Route::get(
            '/dashboard',
            [
                DashboardController::class,
                'index',
            ]
        )->name(
            'dashboard'
        );

        /*
         * Denuncias
         */
        Route::get(
            '/denuncias',
            [
                DenunciaController::class,
                'index',
            ]
        )->name(
            'denuncias.index'
        );

        /*
         * Fotografías privadas ciudadanas.
         */
        Route::get(
            '/denuncias/{denuncia}/fotos/{foto}',
            [
                FotoDenunciaController::class,
                'show',
            ]
        )
            ->whereNumber(
                'denuncia'
            )
            ->whereNumber(
                'foto'
            )
            ->name(
                'denuncias.fotos.show'
            );

        /*
         * Fotografías privadas de resolución.
         *
         * El archivo debe pertenecer a una
         * asignación de la denuncia indicada.
         */
        Route::get(
            '/denuncias/{denuncia}/asignaciones/{asignacion}/fotos/{foto}',
            [
                FotoResolucionController::class,
                'show',
            ]
        )
            ->whereNumber('denuncia')
            ->whereNumber('asignacion')
            ->whereNumber('foto')
            ->name(
                'denuncias.resoluciones.fotos.show'
            );

        Route::get(
            '/denuncias/{denuncia}/edit',
            [
                DenunciaController::class,
                'edit',
            ]
        )->name(
            'denuncias.edit'
        );

        Route::patch(
            '/denuncias/{denuncia}',
            [
                DenunciaController::class,
                'update',
            ]
        )->name(
            'denuncias.update'
        );

        Route::delete(
            '/denuncias/{denuncia}',
            [
                DenunciaController::class,
                'destroy',
            ]
        )->name(
            'denuncias.destroy'
        );

        Route::get(
            '/denuncias/{denuncia}',
            [
                DenunciaController::class,
                'show',
            ]
        )->name(
            'denuncias.show'
        );

        Route::patch(
            '/denuncias/{denuncia}/estado',
            [
                DenunciaController::class,
                'updateEstado',
            ]
        )->name(
            'denuncias.updateEstado'
        );

        Route::post(
            '/denuncias/{denuncia}/asignar-manual',
            [
                DenunciaController::class,
                'asignarManual',
            ]
        )->name(
            'denuncias.asignarManual'
        );

        Route::post(
            '/denuncias/{denuncia}/asignar-automatico',
            [
                DenunciaController::class,
                'asignarAutomatico',
            ]
        )->name(
            'denuncias.asignarAutomatico'
        );

        Route::post(
            '/denuncias/{denuncia}/desasignar',
            [
                DenunciaController::class,
                'desasignar',
            ]
        )->name(
            'denuncias.desasignar'
        );

        Route::post(
            '/denuncias/{denuncia}/verificar-resolucion',
            [
                DenunciaController::class,
                'verificarResolucion',
            ]
        )->name(
            'denuncias.verificarResolucion'
        );

        /*
         * Barrios
         */
        Route::patch(
            '/barrios/{barrio}/municipio',
            [
                BarrioController::class,
                'asignarMunicipio',
            ]
        )->name(
            'barrios.asignarMunicipio'
        );

        Route::resource(
            'barrios',
            BarrioController::class
        )->only([
            'index',
            'store',
            'edit',
            'update',
            'destroy',
        ]);

        /*
         * Municipios
         */
        Route::resource(
            'municipios',
            MunicipioController::class
        )->only([
            'index',
            'store',
            'edit',
            'update',
            'destroy',
        ]);

        /*
         * Camiones
         */
        Route::patch(
            '/camiones/{camion}/municipio',
            [
                CamionController::class,
                'asignarMunicipio',
            ]
        )->name(
            'camiones.asignarMunicipio'
        );

        Route::resource(
            'camiones',
            CamionController::class
        )->parameters([
            'camiones' =>
                'camion',
        ]);
    });