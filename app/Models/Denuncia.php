<?php

namespace App\Models;

use App\Enums\DenunciaEstado;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Denuncia extends Model
{
    public const HORAS_PARA_FINALIZAR = 1;

    /**
     * Solamente datos propios/editables de la denuncia.
     *
     * No incluimos:
     * - user_id
     * - estado
     * - estado_cambiado_at
     *
     * porque son campos controlados por el sistema.
     */
    protected $fillable = [
        'barrio_id',
        'municipio_id',
        'direccion',
        'latitud',
        'longitud',
        'descripcion',
    ];

    protected $casts = [
        'estado_cambiado_at' => 'datetime',
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
    ];

    public function estadoEnum(): DenunciaEstado
    {
        return DenunciaEstado::from(
            $this->estado
        );
    }

    public function estadoLabel(): string
    {
        return $this
            ->estadoEnum()
            ->label();
    }

    public function puedeCambiarEstadoA(
        DenunciaEstado $nuevoEstado
    ): bool {
        return $this
            ->estadoEnum()
            ->puedeTransicionarA(
                $nuevoEstado
            );
    }

    public function cambiarEstado(
        DenunciaEstado $nuevoEstado
    ): void {
        $estadoActual =
            $this->estadoEnum();

        if ($estadoActual === $nuevoEstado) {
            return;
        }

        if (
            !$estadoActual->puedeTransicionarA(
                $nuevoEstado
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'La transición de "%s" a "%s" no está permitida.',
                    $estadoActual->label(),
                    $nuevoEstado->label()
                )
            );
        }

        $this->estado =
            $nuevoEstado->value;

        $this->estado_cambiado_at =
            now();

        $this->save();
    }

    /**
     * Fuerza un cambio de estado administrativo.
     *
     * Este método no valida las transiciones normales del flujo.
     * Debe utilizarse únicamente después de comprobar que el usuario
     * autenticado posee rol de administrador.
     */
    public function cambiarEstadoAdministrativamente(
        DenunciaEstado $nuevoEstado
    ): void {
        $estadoActual =
            $this->estadoEnum();

        if ($estadoActual === $nuevoEstado) {
            return;
        }

        $this->estado =
            $nuevoEstado->value;

        /*
         * Toda corrección administrativa de estado reinicia
         * el contador correspondiente al nuevo estado.
         */
        $this->estado_cambiado_at =
            now();

        $this->save();
    }

    public function puedeFinalizar(): bool
    {
        if (
            $this->estadoEnum()
            !== DenunciaEstado::RESUELTO
        ) {
            return false;
        }

        if (!$this->estado_cambiado_at) {
            return false;
        }

        return $this
            ->estado_cambiado_at
            ->lte(
                now()->subHours(
                    self::HORAS_PARA_FINALIZAR
                )
            );
    }

    /**
     * Minutos restantes para la finalización automática.
     * Se utiliza en mensajes para un plazo inferior a 48 horas.
     */
    public function minutosParaFinalizar(): int
    {
        if ($this->estadoEnum() !== DenunciaEstado::RESUELTO) {
            return 0;
        }

        if (!$this->estado_cambiado_at || $this->estado_cambiado_at->isFuture()) {
            return self::HORAS_PARA_FINALIZAR * 60;
        }

        return max(
            0,
            (int) ceil(now()->diffInSeconds(
                $this->estado_cambiado_at->copy()->addHours(self::HORAS_PARA_FINALIZAR),
                false
            ) / 60)
        );
    }

    public function horasParaFinalizar(): int
    {
        if (
            $this->estadoEnum()
            !== DenunciaEstado::RESUELTO
        ) {
            return 0;
        }

        if (!$this->estado_cambiado_at) {
            return self::HORAS_PARA_FINALIZAR;
        }

        /*
         * Nunca interpretamos una fecha futura como horas
         * ya transcurridas. Esto protege el contador frente
         * a registros históricos creados con otra zona horaria.
         */
        if ($this->estado_cambiado_at->isFuture()) {
            return self::HORAS_PARA_FINALIZAR;
        }

        $horasTranscurridas =
            (int) $this
                ->estado_cambiado_at
                ->diffInHours(
                    now(),
                    true
                );

        return max(
            0,
            self::HORAS_PARA_FINALIZAR
                - $horasTranscurridas
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function barrio()
    {
        return $this->belongsTo(
            Barrio::class
        );
    }

    public function municipio()
    {
        return $this->belongsTo(
            Municipio::class
        );
    }

    public function fotos()
    {
        return $this->hasMany(
            FotoDenuncia::class
        );
    }

    public function reclamaciones()
    {
        return $this->hasMany(
            Reclamacion::class
        );
    }

    public function asignaciones()
    {
        return $this->hasMany(
            DenunciaCamion::class
        );
    }
}