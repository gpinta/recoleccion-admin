<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Denuncia;
use App\Models\FotoDenuncia;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FotoDenunciaController extends Controller
{
    public function show(
        Denuncia $denuncia,
        FotoDenuncia $foto
    ): StreamedResponse {
        /*
         * La fotografía debe pertenecer exactamente
         * a la denuncia indicada en la URL.
         */
        abort_unless(
            $foto->denuncia_id
                === $denuncia->id,
            404
        );

        /*
         * Autorización administrativa explícita.
         */
        Gate::authorize(
            'verFoto',
            $denuncia
        );

        /*
         * El registro puede existir aunque el archivo
         * físico haya desaparecido.
         */
        abort_unless(
            Storage::disk(
                'denuncias_private'
            )->exists(
                $foto->ruta
            ),
            404
        );

        return Storage::disk(
            'denuncias_private'
        )->response(
            $foto->ruta,
            null,
            [
                'Cache-Control' =>
                    'private, max-age=3600',
            ]
        );
    }
}