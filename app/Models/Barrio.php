<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barrio extends Model
{
    protected $fillable = [
        'municipio_id',
        'nombre',
    ];

    public function municipio()
    {
        return $this->belongsTo(
            Municipio::class
        );
    }

    public function denuncias()
    {
        return $this->hasMany(
            Denuncia::class
        );
    }
}