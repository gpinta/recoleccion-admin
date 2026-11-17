<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /**
     * Modelo ciudadano de la base compartida.
     *
     * "activo" fue descartado del diseño y
     * ya no forma parte del esquema actual.
     */
    protected $fillable = [
        'documento',
        'name',
        'email',
        'telefono',
    ];

    public function denuncias()
    {
        return $this->hasMany(
            Denuncia::class
        );
    }

    public function reclamaciones()
    {
        return $this->hasMany(
            Reclamacion::class
        );
    }
}