<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FotoResolucion extends Model
{
    protected $table = 'fotos_resolucion';

    protected $guarded = [
        '*',
    ];

    public function denunciaCamion()
    {
        return $this->belongsTo(
            DenunciaCamion::class
        );
    }
}