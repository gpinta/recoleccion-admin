<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CamionUser extends Model
{
    /*
     * Cuenta utilizada por la Aplicación Camiones.
     *
     * No representa un usuario LDAP del Backend
     * Administrativo ni un usuario ciudadano.
     */
    protected $table = 'camion_user';

    /*
     * La contraseña es administrada explícitamente
     * desde el controlador autorizado.
     */
    protected $guarded = ['*'];

    /*
     * Nunca exponer el hash al convertir
     * el modelo en un array o JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function camion(): BelongsTo
    {
        return $this->belongsTo(
            Camion::class,
            'camion_id'
        );
    }
}