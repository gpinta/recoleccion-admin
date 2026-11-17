<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DenunciaEstado;
use App\Http\Controllers\Controller;
use App\Models\Camion;
use App\Models\Denuncia;

class DashboardController extends Controller
{
    public function index()
    {
        $pendientes = Denuncia::where(
            'estado',
            DenunciaEstado::PENDIENTE->value
        )->count();

        $enProceso = Denuncia::where(
            'estado',
            DenunciaEstado::EN_PROCESO->value
        )->count();

        $resueltas = Denuncia::where(
            'estado',
            DenunciaEstado::RESUELTO->value
        )->count();

        $rechazadas = Denuncia::where(
            'estado',
            DenunciaEstado::RECHAZADO->value
        )->count();

        $finalizadas = Denuncia::where(
            'estado',
            DenunciaEstado::FINALIZADO->value
        )->count();

        $totalCamiones =
            Camion::count();

        $camionesActivos =
            Camion::where(
                'estado_operativo',
                'activo'
            )->count();

        $camionesInactivos =
            Camion::where(
                'estado_operativo',
                'inactivo'
            )->count();

        /*
         * Un camión está asignado únicamente
         * si posee una asignación pendiente
         * o en proceso.
         */
        $camionesAsignados =
            Camion::whereHas(
                'asignacionesActivas'
            )->count();

        /*
         * Solo consideramos como "sin asignar"
         * denuncias que todavía forman parte
         * del flujo operativo.
         *
         * Una denuncia rechazada/finalizada no debe
         * aparecer como problema de asignación.
         */
        $denunciasSinAsignar =
            Denuncia::whereIn(
                'estado',
                [
                    DenunciaEstado::PENDIENTE->value,
                    DenunciaEstado::EN_PROCESO->value,
                ]
            )
            ->whereDoesntHave(
                'asignaciones',
                function ($query) {
                    $query->whereIn(
                        'estado_asignacion',
                        [
                            'pendiente',
                            'en_proceso',
                        ]
                    );
                }
            )
            ->count();

        return view(
            'admin.dashboard',
            compact(
                'pendientes',
                'enProceso',
                'resueltas',
                'rechazadas',
                'finalizadas',
                'totalCamiones',
                'camionesActivos',
                'camionesInactivos',
                'camionesAsignados',
                'denunciasSinAsignar'
            )
        );
    }
}