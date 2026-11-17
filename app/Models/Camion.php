<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Camion extends Model
{
    protected $table = 'camiones';

    /**
     * Campos administrativos editables.
     *
     * "asignado" deliberadamente NO aparece aquí.
     * Su valor se calcula a partir de las asignaciones
     * activas del camión.
     */
    protected $fillable = [
        'matricula',
        'marca',
        'modelo',
        'estado_operativo',
        'municipio_id',
    ];

    public function municipio()
    {
        return $this->belongsTo(
            Municipio::class
        );
    }

    public function asignaciones()
    {
        return $this->hasMany(
            DenunciaCamion::class
        );
    }

    /*
     * Cuenta de acceso a la Aplicación Camiones.
     *
     * La matrícula permanece en camiones;
     * camion_user almacena las credenciales.
     */
    public function usuario(): HasOne
    {
        return $this->hasOne(
            CamionUser::class,
            'camion_id'
        );
    }

    /**
     * Asignaciones que actualmente requieren
     * trabajo por parte del camión.
     */
    public function asignacionesActivas()
    {
        return $this
            ->hasMany(
                DenunciaCamion::class
            )
            ->whereIn(
                'estado_asignacion',
                [
                    'pendiente',
                    'en_proceso',
                ]
            );
    }

    /**
     * Propiedad calculada:
     *
     * $camion->asignado
     *
     * Ya no depende de la columna histórica
     * camiones.asignado.
     */
    public function getAsignadoAttribute(): bool
    {
        /*
         * Si el controlador utilizó withExists(),
         * aprovechamos ese dato y evitamos otra consulta.
         */
        if (
            array_key_exists(
                'asignaciones_activas_exists',
                $this->attributes
            )
        ) {
            return (bool)
                $this->attributes[
                    'asignaciones_activas_exists'
                ];
        }

        /*
         * Si ya cargamos todas las asignaciones,
         * podemos resolverlo en memoria.
         */
        if (
            $this->relationLoaded(
                'asignaciones'
            )
        ) {
            return $this
                ->asignaciones
                ->contains(
                    fn (DenunciaCamion $asignacion) =>
                        in_array(
                            $asignacion->estado_asignacion,
                            [
                                'pendiente',
                                'en_proceso',
                            ],
                            true
                        )
                );
        }

        /*
         * Último recurso: consulta directa.
         */
        return $this
            ->asignacionesActivas()
            ->exists();
    }
}