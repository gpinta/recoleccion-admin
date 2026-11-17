<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    protected $fillable = [
        'nombre',
    ];

    public function barrios()
    {
        return $this->hasMany(
            Barrio::class
        );
    }

    public function denuncias()
    {
        return $this->hasMany(
            Denuncia::class
        );
    }

    public function camiones()
    {
        return $this->hasMany(
            Camion::class
        );
    }
}