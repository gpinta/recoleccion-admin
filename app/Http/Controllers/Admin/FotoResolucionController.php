<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Denuncia;
use App\Models\FotoResolucion;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FotoResolucionController extends Controller
{
    /**
     * Mostrar una fotografía privada de resolución.
     *
     * La autorización utiliza la misma política
     * administrativa existente para la lectura
     * de fotografías de denuncias.
     */
    public function show(
        Denuncia $denuncia,
        int $asignacion,
        int $foto
    ): StreamedResponse {
        /*
         * Autorización administrativa explícita.
         *
         * Se reutiliza DenunciaPolicy::verFoto()
         * del Backend Administrativo.
         */
        Gate::authorize(
            'verFoto',
            $denuncia
        );

        /*
         * La asignación debe pertenecer exactamente
         * a la denuncia indicada en la URL.
         *
         * No se permite consultar una asignación
         * perteneciente a otra denuncia.
         */
        $asignacionValida = $denuncia
            ->asignaciones()
            ->whereKey($asignacion)
            ->firstOrFail();

        /*
         * La fotografía debe pertenecer exactamente
         * a la asignación comprobada.
         *
         * Se conserva la trazabilidad mediante
         * fotos_resolucion.denuncia_camion_id.
         */
        $evidencia = FotoResolucion::query()
            ->where(
                'denuncia_camion_id',
                $asignacionValida->id
            )
            ->findOrFail($foto);

        /*
         * La ruta privada se obtiene de MySQL.
         *
         * Estructura acordada:
         *
         * {denuncia_id}/foto_resolucion/{archivo}
         *
         * El identificador del directorio es
         * denuncias.id y no denuncia_camion.id.
         */
        $ruta = $evidencia->getRawOriginal('ruta');

        $prefijo = $denuncia->id.'/foto_resolucion/';

        /*
         * Validamos que el archivo se encuentre
         * dentro de la carpeta de resolución
         * de esta denuncia.
         *
         * No admitimos rutas absolutas ni segmentos
         * para navegar hacia otros directorios.
         */
        abort_unless(
            is_string($ruta)
            && str_starts_with($ruta, $prefijo)
            && preg_match(
                '/\A[A-Za-z0-9._-]+\z/D',
                substr($ruta, strlen($prefijo))
            ) === 1
            && !str_contains($ruta, '..'),
            404
        );

        /*
         * Utilizamos el mismo disco privado
         * que ya existe en Administración.
         *
         * El disco apunta a la raíz física
         * compartida /fotos.
         *
         * No se genera una URL pública.
         */
        $disk = Storage::disk('denuncias_private');

        /*
         * El registro puede existir aunque el
         * archivo físico haya desaparecido.
         */
        abort_unless(
            $disk->exists($ruta),
            404
        );

        /*
         * La fotografía se entrega solamente
         * después de comprobar autorización,
         * denuncia, asignación y ruta.
         */
        return $disk->response(
            $ruta,
            null,
            [
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}