<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DenunciaCamion extends Model
{
    protected $table = 'denuncia_camion';

    /**
     * El único dato que permitimos asignar masivamente
     * es el comentario de cierre.
     *
     * denuncia_id, camion_id y estado_asignacion
     * son campos internos del flujo.
     */
    protected $fillable = [
        'comentario_cierre',
    ];

    public function denuncia()
    {
        return $this->belongsTo(
            Denuncia::class
        );
    }

    public function camion()
    {
        return $this->belongsTo(
            Camion::class
        );
    }

    public function fotos()
    {
        return $this->hasMany(
            FotoResolucion::class
        );
    }
}