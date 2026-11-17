<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reclamacion extends Model
{
    protected $table = 'reclamaciones';

    protected $guarded = [
        '*',
    ];

    public function denuncia()
    {
        return $this->belongsTo(
            Denuncia::class
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }
}