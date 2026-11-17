<?php

namespace App\Enums;

enum DenunciaEstado: string
{
    case PENDIENTE = 'pendiente';

    case EN_PROCESO = 'en_proceso';

    case RESUELTO = 'resuelto';

    case RECLAMADO = 'reclamado';

    case RECHAZADO = 'rechazado';

    case FINALIZADO = 'finalizado';

    /**
     * Nombre amigable del estado.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::EN_PROCESO => 'En proceso',
            self::RESUELTO => 'Resuelto',
            self::RECLAMADO => 'Reclamado',
            self::RECHAZADO => 'Rechazado',
            self::FINALIZADO => 'Finalizado',
        };
    }

    /**
     * Transiciones válidas del flujo completo.
     *
     * Importante:
     * - RESUELTO -> FINALIZADO existe como transición válida
     *   del dominio y se ejecuta mediante el proceso de finalización.
     * - RECHAZADO es una salida administrativa excepcional y puede
     *   alcanzarse desde cualquier otro estado.
     */
    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::PENDIENTE => [
                self::EN_PROCESO,
                self::RECHAZADO,
            ],

            self::EN_PROCESO => [
                self::RESUELTO,
                self::RECHAZADO,
            ],

            self::RESUELTO => [
                self::FINALIZADO,
                self::RECHAZADO,
            ],

            /*
             * Todo reclamo se revisa antes de continuar.
             * El operador puede reabrirlo o rechazarlo.
             */
            self::RECLAMADO => [
                self::PENDIENTE,
                self::EN_PROCESO,
                self::RECHAZADO,
            ],

            self::FINALIZADO => [
                self::RECHAZADO,
            ],

            self::RECHAZADO => [],
        };
    }

    /**
     * Determina si desde el estado actual
     * puede realizarse una transición determinada.
     */
    public function puedeTransicionarA(
        self $nuevoEstado
    ): bool {
        foreach (
            $this->transicionesPermitidas()
            as $estadoPermitido
        ) {
            if ($estadoPermitido === $nuevoEstado) {
                return true;
            }
        }

        return false;
    }

    /**
     * Estados terminales del flujo.
     */
    public function esTerminal(): bool
    {
        return match ($this) {
            self::RECHAZADO,
            self::FINALIZADO => true,

            default => false,
        };
    }

    /**
     * Valores válidos para validaciones.
     */
    public static function values(): array
    {
        return array_map(
            fn (self $estado) => $estado->value,
            self::cases()
        );
    }
}