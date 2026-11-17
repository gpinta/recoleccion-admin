<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FotoDenuncia extends Model
{
    protected $table = 'fotos_denuncia';

    /**
     * Este modelo es de solo lectura dentro
     * del Backend Administrativo.
     */
    protected $guarded = [
        '*',
    ];

    public function denuncia()
    {
        return $this->belongsTo(
            Denuncia::class
        );
    }
}