<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
|
| Finaliza automáticamente denuncias que permanecen en estado Resuelto
| durante el plazo definido por el dominio.
|
*/

Schedule::command(
    'denuncias:finalizar-vencidas'
)
    ->everyMinute()
    ->withoutOverlapping(5);
