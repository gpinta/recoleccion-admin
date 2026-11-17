<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DenunciaEstado;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AsignarDenunciaManualRequest;
use App\Http\Requests\Admin\DenunciaIndexRequest;
use App\Http\Requests\Admin\UpdateDenunciaAdminRequest;
use App\Http\Requests\Admin\UpdateDenunciaEstadoRequest;
use App\Models\Barrio;
use App\Models\Camion;
use App\Models\Denuncia;
use App\Models\DenunciaCamion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DenunciaController extends Controller
{
    public function index(
        DenunciaIndexRequest $request
    ) {
        Gate::authorize(
            'viewAny',
            Denuncia::class
        );

        $validated =
            $request->validated();

        $query = Denuncia::with([
            'user',
            'barrio',
            'municipio',
        ]);

        if (
            !empty(
                $validated['estado']
            )
        ) {
            $query->where(
                'estado',
                $validated['estado']
            );
        }

        if (
            !empty(
                $validated['buscar']
            )
        ) {
            $query->where(
                'id',
                $validated['buscar']
            );
        }

        $denuncias = $query
            ->latest()
            ->paginate(15)
            ->appends(
                $request->query()
            );

        return view(
            'admin.denuncias.index',
            compact('denuncias')
        );
    }

    public function show(
        Denuncia $denuncia
    ) {
        Gate::authorize(
            'view',
            $denuncia
        );

        $denuncia->load([
            'user',
            'barrio',
            'municipio',
            'fotos',
            'reclamaciones.user',
            'asignaciones.camion',
            'asignaciones.fotos',
        ]);

        /*
         * Temporalmente utilizado por las fotografías
         * de evidencia de la Aplicación Camiones.
         */
        $apiUrl =
            env('API_PUBLIC_URL');

        /*
         * Para la interfaz solo mostramos camiones:
         *
         * - activos;
         * - pertenecientes al mismo municipio
         *   de la denuncia.
         *
         * La validación real se vuelve a realizar
         * en asignarManual(), ya que nunca debemos confiar
         * solamente en lo que muestra un <select>.
         */
        /*
         * Los operadores no necesitan un listado de camiones
         * en denuncias finalizadas o rechazadas.
         * Administración conserva el comportamiento habitual.
         */
        $mostrarCamiones = $denuncia->estadoEnum()->esTerminal()
            ? (bool) auth()->user()?->esBackendAdmin()
            : true;

        $camiones = $mostrarCamiones
            ? Camion::query()
            ->where(
                'municipio_id',
                $denuncia->municipio_id
            )
            ->where(
                'estado_operativo',
                'activo'
            )
            ->with('municipio')
            ->orderBy('matricula')
            ->get()
            : collect();

        return view(
            'admin.denuncias.show',
            compact(
                'denuncia',
                'apiUrl',
                'camiones'
            )
        );
    }

    public function edit(
        Denuncia $denuncia
    ) {
        Gate::authorize(
            'update',
            $denuncia
        );

        $denuncia->load([
            'barrio',
            'municipio',
        ]);

        $barrios = Barrio::query()
            ->with('municipio')
            ->orderBy('nombre')
            ->get();

        return view(
            'admin.denuncias.edit',
            compact(
                'denuncia',
                'barrios'
            )
        );
    }

    public function update(
        UpdateDenunciaAdminRequest $request,
        Denuncia $denuncia
    ) {
        Gate::authorize(
            'update',
            $denuncia
        );

        $validated =
            $request->validated();

        DB::transaction(
            function () use (
                $denuncia,
                $validated
            ): void {
                $denunciaBloqueada =
                    Denuncia::query()
                        ->whereKey(
                            $denuncia->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                /*
                 * El municipio nunca se recibe desde el formulario.
                 * Se deriva del barrio seleccionado para mantener
                 * coherencia entre barrio y municipio.
                 */
                $barrio =
                    Barrio::query()
                        ->whereKey(
                            $validated['barrio_id']
                        )
                        ->firstOrFail();

                $municipioAnterior =
                    $denunciaBloqueada
                        ->municipio_id;

                $denunciaBloqueada->direccion =
                    $validated['direccion'];

                $denunciaBloqueada->barrio_id =
                    $barrio->id;

                $denunciaBloqueada->municipio_id =
                    $barrio->municipio_id;

                $denunciaBloqueada->descripcion =
                    $validated['descripcion']
                    ?? null;

                $denunciaBloqueada->save();

                /*
                 * Si el cambio de barrio también cambia el municipio,
                 * la denuncia CONSERVA su estado actual.
                 *
                 * Únicamente cerramos la asignación activa del camión
                 * anterior porque ya no pertenece al municipio correcto.
                 *
                 * Después del cambio la denuncia queda sin asignación
                 * activa y un operador deberá realizar una nueva
                 * asignación manual o automática.
                 *
                 * La tabla denuncia_camion todavía no posee un estado
                 * "desasignado"; por compatibilidad con la estructura
                 * actual se utiliza "rechazado" solamente sobre la
                 * ASIGNACIÓN, nunca sobre denuncias.estado.
                 */
                if (
                    (int) $municipioAnterior
                    !==
                    (int) $barrio->municipio_id
                ) {
                    $denunciaBloqueada
                        ->asignaciones()
                        ->whereIn(
                            'estado_asignacion',
                            [
                                'pendiente',
                                'en_proceso',
                            ]
                        )
                        ->update([
                            'estado_asignacion' =>
                                'rechazado',

                            'comentario_cierre' =>
                                'Asignación cerrada automáticamente por cambio de municipio de la denuncia.',
                        ]);
                }
            }
        );

        return redirect()
            ->route(
                'admin.denuncias.show',
                $denuncia
            )
            ->with(
                'success',
                'Denuncia actualizada correctamente.'
            );
    }

    public function destroy(
        Denuncia $denuncia
    ) {
        Gate::authorize(
            'delete',
            $denuncia
        );

        /*
         * Las fotografías ciudadanas y las fotografías de resolución
         * están en el volumen compartido denuncias_private.
         *
         * El Backend Administrativo dispone de escritura sobre
         * ese volumen exclusivamente para poder realizar una
         * eliminación integral de la denuncia.
         *
         * La estructura actual del almacenamiento es:
         *
         * {denuncia_id}/foto_denuncia/
         * {denuncia_id}/foto_resolucion/
         *
         * Eliminamos el directorio completo correspondiente
         * al ID real de la denuncia.
         *
         * Primero eliminamos los archivos físicos y luego el registro.
         * Las FK de MySQL eliminan en cascada fotos_denuncia,
         * reclamaciones, denuncia_camion y fotos_resolucion
         * según las relaciones definidas en las migraciones.
         */
        DB::transaction(
            function () use (
                $denuncia
            ): void {
                $denunciaBloqueada =
                    Denuncia::query()
                        ->whereKey(
                            $denuncia->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                /*
                 * El nombre del directorio se obtiene
                 * exclusivamente del ID de la denuncia
                 * recuperada desde la base de datos.
                 *
                 * Nunca se utiliza una ruta proporcionada
                 * directamente por el navegador.
                 */
                $directorio = (string) $denunciaBloqueada->id;

                $disk = Storage::disk(
                    'denuncias_private'
                );

                /*
                 * Si la carpeta ya no existe, no impedimos
                 * eliminar el registro huérfano de BD.
                 *
                 * Esto permite eliminar también denuncias
                 * que no tengan fotografías.
                 */
                if ($disk->directoryExists($directorio)) {

                    /*
                     * Se elimina recursivamente la carpeta:
                     *
                     * {denuncia_id}/
                     *
                     * Esto incluye:
                     *
                     * - foto_denuncia/
                     * - foto_resolucion/
                     * - todos sus archivos y subdirectorios.
                     *
                     * No se elimina ni modifica la carpeta
                     * de ninguna otra denuncia.
                     */
                    if (!$disk->deleteDirectory($directorio)) {
                        throw ValidationException::withMessages([
                            'denuncia' => [
                                sprintf(
                                    'No fue posible eliminar la carpeta de fotografías de la denuncia #%d. La denuncia no fue eliminada.',
                                    $denunciaBloqueada->id
                                ),
                            ],
                        ]);
                    }
                }

                /*
                 * Eliminamos el registro de la denuncia.
                 *
                 * Las relaciones con eliminación en cascada
                 * se encargan de retirar los registros
                 * dependientes de la base de datos.
                 */
                $denunciaBloqueada->delete();
            }
        );

        return redirect()
            ->route(
                'admin.denuncias.index'
            )
            ->with(
                'success',
                'Denuncia y fotografías eliminadas correctamente.'
            );
    }

    /**
     * Asignación manual.
     *
     * Todo el proceso se realiza dentro de una única
     * transacción:
     *
     * 1. bloquear denuncia;
     * 2. validar que pueda asignarse;
     * 3. bloquear camión;
     * 4. validar camión;
     * 5. cerrar asignación anterior;
     * 6. crear asignación nueva.
     */
    public function asignarManual(
        AsignarDenunciaManualRequest $request,
        Denuncia $denuncia
    ) {
        Gate::authorize(
            'asignar',
            $denuncia
        );

        $validated =
            $request->validated();

        $camion = DB::transaction(
            function () use (
                $denuncia,
                $validated
            ) {
                /*
                 * Bloqueamos la fila de denuncia.
                 *
                 * Dos administradores no podrán modificar
                 * simultáneamente sus asignaciones mediante
                 * estos métodos.
                 */
                $denunciaBloqueada =
                    Denuncia::query()
                        ->whereKey(
                            $denuncia->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->validarDenunciaAsignable(
                    $denunciaBloqueada
                );

                /*
                 * Volvemos a consultar el camión dentro
                 * de la transacción y bloqueamos su fila.
                 */
                $camionBloqueado =
                    Camion::query()
                        ->whereKey(
                            $validated['camion_id']
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->validarCamionParaDenuncia(
                    $camionBloqueado,
                    $denunciaBloqueada
                );

                /*
                 * Evitamos cerrar y volver a crear
                 * exactamente la misma asignación.
                 */
                $asignacionActual =
                    $denunciaBloqueada
                        ->asignaciones()
                        ->whereIn(
                            'estado_asignacion',
                            [
                                'pendiente',
                                'en_proceso',
                            ]
                        )
                        ->latest()
                        ->first();

                if (
                    $asignacionActual
                    && $asignacionActual->camion_id
                        === $camionBloqueado->id
                ) {
                    throw ValidationException::withMessages([
                        'camion_id' => [
                            'La denuncia ya se encuentra asignada a ese camión.',
                        ],
                    ]);
                }

                $this->reemplazarAsignacionActiva(
                    $denunciaBloqueada,
                    $camionBloqueado
                );

                return $camionBloqueado;
            }
        );

        return back()->with(
            'success',
            'Denuncia asignada manualmente al camión '
                . $camion->matricula
        );
    }

    /**
     * Asignación automática.
     */
    public function asignarAutomatico(
        Denuncia $denuncia
    ) {
        Gate::authorize(
            'asignar',
            $denuncia
        );

        $camion = DB::transaction(
            function () use (
                $denuncia
            ) {
                $denunciaBloqueada =
                    Denuncia::query()
                        ->whereKey(
                            $denuncia->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->validarDenunciaAsignable(
                    $denunciaBloqueada
                );

                /*
                 * Solo consideramos camiones:
                 *
                 * - activos;
                 * - del municipio de la denuncia.
                 *
                 * Se conserva la estrategia actual
                 * de elegir el que tenga menos
                 * asignaciones activas.
                 */
                $camionId =
                    Camion::query()
                        ->where(
                            'municipio_id',
                            $denunciaBloqueada->municipio_id
                        )
                        ->where(
                            'estado_operativo',
                            'activo'
                        )
                        ->withCount([
                            'asignaciones as activas_count' =>
                                function ($query) {
                                    $query->whereIn(
                                        'estado_asignacion',
                                        [
                                            'pendiente',
                                            'en_proceso',
                                        ]
                                    );
                                },
                        ])
                        ->orderBy(
                            'activas_count'
                        )
                        ->orderBy('id')
                        ->value('id');

                if (!$camionId) {
                    throw ValidationException::withMessages([
                        'camion' => [
                            'No hay camiones activos disponibles en este municipio.',
                        ],
                    ]);
                }

                /*
                 * Bloqueamos el camión seleccionado
                 * antes de modificar la asignación.
                 */
                $camionBloqueado =
                    Camion::query()
                        ->whereKey(
                            $camionId
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                /*
                 * Revalidamos dentro de la transacción.
                 */
                $this->validarCamionParaDenuncia(
                    $camionBloqueado,
                    $denunciaBloqueada
                );

                $this->reemplazarAsignacionActiva(
                    $denunciaBloqueada,
                    $camionBloqueado
                );

                return $camionBloqueado;
            }
        );

        return back()->with(
            'success',
            'Denuncia asignada automáticamente al camión '
                . $camion->matricula
        );
    }

    /**
     * Desasignación administrativa.
     *
     * Cierra las asignaciones activas de una denuncia
     * sin eliminar los registros ni sus fotografías.
     *
     * Se utiliza el estado "rechazado" porque es
     * el mecanismo existente para cerrar asignaciones.
     *
     * No modifica denuncias.estado.
     */
    public function desasignar(
        Denuncia $denuncia
    ) {
        Gate::authorize(
            'desasignar',
            $denuncia
        );

        DB::transaction(
            function () use ($denuncia): void {

                /*
                 * Bloqueamos primero la denuncia para
                 * evitar modificaciones concurrentes.
                 */
                $denunciaBloqueada =
                    Denuncia::query()
                        ->whereKey(
                            $denuncia->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                /*
                 * Revalidamos los permisos sobre
                 * el estado actualizado.
                 */
                Gate::authorize(
                    'desasignar',
                    $denunciaBloqueada
                );

                /*
                 * Buscamos asignaciones activas.
                 *
                 * El historial puede tener múltiples
                 * asignaciones, pero solamente cerramos
                 * las que siguen operativas.
                 */
                $asignacionesActivas =
                    $denunciaBloqueada
                        ->asignaciones()
                        ->whereIn(
                            'estado_asignacion',
                            [
                                'pendiente',
                                'en_proceso',
                            ]
                        )
                        ->lockForUpdate()
                        ->get();

                /*
                 * Evitamos desasignaciones duplicadas
                 * o peticiones sin asignación activa.
                 */
                if ($asignacionesActivas->isEmpty()) {
                    throw ValidationException::withMessages([
                        'asignacion' => [
                            'La denuncia no posee asignaciones activas para desasignar.',
                        ],
                    ]);
                }

                /*
                 * Cerramos las asignaciones sin eliminarlas.
                 *
                 * Se conserva:
                 * - denuncia_id
                 * - camion_id
                 * - ID de la asignación
                 * - fotografías de resolución
                 * - historial de asignaciones
                 */
                foreach ($asignacionesActivas as $asignacion) {

                    $asignacion->estado_asignacion =
                        'rechazado';

                    $asignacion->comentario_cierre =
                        'Asignación cerrada por desasignación administrativa.';

                    $asignacion->save();
                }

                /*
                 * No cambiamos el estado de la denuncia.
                 *
                 * Esto conserva el comportamiento
                 * del resto del sistema.
                 *
                 * Después de desasignar, la denuncia
                 * podrá recibir una nueva asignación
                 * manual o automática, siempre que
                 * su estado permita asignarla.
                 */
            },
            3
        );

        return redirect()
            ->route(
                'admin.denuncias.show',
                $denuncia
            )
            ->with(
                'success',
                'Camión desasignado correctamente. La denuncia puede asignarse nuevamente.'
            );
    }

    public function verificarResolucion(
        Denuncia $denuncia
    ) {
        Gate::authorize(
            'verificarResolucion',
            $denuncia
        );

        /*
         * Revalidamos estado y plazo bajo bloqueo: un reclamo
         * concurrente no puede terminar en una finalización
         * administrativa basada en información anterior.
         */
        DB::transaction(function () use ($denuncia): void {
            $denunciaBloqueada = Denuncia::query()
                ->whereKey($denuncia->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($denunciaBloqueada->estadoEnum() !== DenunciaEstado::RESUELTO) {
                throw ValidationException::withMessages([
                    'estado' => 'Solo pueden finalizarse denuncias que se encuentren en estado Resuelto.',
                ]);
            }

            $asignacion = $denunciaBloqueada
                ->asignaciones()
                ->where('estado_asignacion', 'resuelto')
                ->latest()
                ->first();

            if (!$asignacion) {
                throw ValidationException::withMessages([
                    'asignacion' => 'No existe una resolución de camión pendiente de verificación.',
                ]);
            }

            if (!$denunciaBloqueada->estado_cambiado_at) {
                throw ValidationException::withMessages([
                    'estado' => 'La denuncia no posee una fecha válida de cambio de estado.',
                ]);
            }

            if (!$denunciaBloqueada->puedeFinalizar()) {
                throw ValidationException::withMessages([
                    'estado' => sprintf(
                        'La denuncia todavía no puede finalizarse. Debe transcurrir 1 hora desde que fue marcada como Resuelta. Restan aproximadamente %d minutos.',
                        $denunciaBloqueada->minutosParaFinalizar()
                    ),
                ]);
            }

            $denunciaBloqueada->cambiarEstado(DenunciaEstado::FINALIZADO);
        });

        return back()->with(
            'success',
            'Resolución verificada correctamente. La denuncia ha sido finalizada.'
        );
    }

    public function updateEstado(
        UpdateDenunciaEstadoRequest $request,
        Denuncia $denuncia
    ) {
        Gate::authorize(
            'updateEstado',
            $denuncia
        );

        $validated =
            $request->validated();

        $nuevoEstado =
            DenunciaEstado::from(
                $validated['estado']
            );

        $usuario =
            $request->user();

        $esAdmin =
            $usuario
            && $usuario->esBackendAdmin();

        $resultado = DB::transaction(
            function () use (
                $denuncia,
                $nuevoEstado,
                $esAdmin
            ): array {
                /*
                 * Bloqueamos la denuncia para impedir cambios
                 * simultáneos de estado.
                 */
                $denunciaBloqueada =
                    Denuncia::query()
                        ->whereKey(
                            $denuncia->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $estadoActual =
                    $denunciaBloqueada
                        ->estadoEnum();

                if (
                    $estadoActual ===
                    $nuevoEstado
                ) {
                    return [
                        'sin_cambios' => true,
                        'estado' => $nuevoEstado,
                    ];
                }

                /*
                 * ADMIN
                 *
                 * Puede corregir una denuncia desde cualquier
                 * estado hacia cualquier otro estado válido.
                 *
                 * No se aplican las transiciones normales del enum.
                 */
                if ($esAdmin) {
                    /*
                     * Al entrar en un estado terminal o reclamado cerramos
                     * cualquier asignación activa.
                     *
                     * Esto afecta únicamente a denuncia_camion.
                     * La denuncia queda exactamente en el estado
                     * seleccionado por el administrador.
                     */
                    if (
                        in_array(
                            $nuevoEstado,
                            [
                                DenunciaEstado::RECHAZADO,
                                DenunciaEstado::FINALIZADO,
                                DenunciaEstado::RECLAMADO,
                            ],
                            true
                        )
                    ) {
                        $denunciaBloqueada
                            ->asignaciones()
                            ->whereIn(
                                'estado_asignacion',
                                [
                                    'pendiente',
                                    'en_proceso',
                                ]
                            )
                            ->update([
                                'estado_asignacion' =>
                                    'rechazado',

                                'comentario_cierre' =>
                                    'Asignación cerrada por cambio administrativo del estado de la denuncia.',
                            ]);
                    }

                    $denunciaBloqueada
                        ->cambiarEstadoAdministrativamente(
                            $nuevoEstado
                        );

                    return [
                        'sin_cambios' => false,
                        'estado' => $nuevoEstado,
                    ];
                }

                /*
                 * El operador no puede reabrir ni modificar
                 * denuncias terminales, incluso si envía una
                 * petición directa sin utilizar el formulario.
                 */
                if ($estadoActual->esTerminal()) {
                    throw ValidationException::withMessages([
                        'estado' => 'Las denuncias finalizadas o rechazadas son de solo lectura para operadores.',
                    ]);
                }

                /*
                 * El estado Reclamado es reservado al registro
                 * de un reclamo ciudadano, no se selecciona a mano.
                 */
                if ($nuevoEstado === DenunciaEstado::RECLAMADO) {
                    throw ValidationException::withMessages([
                        'estado' => 'El estado Reclamado se establece al registrar un reclamo ciudadano.',
                    ]);
                }

                /*
                 * OPERATOR
                 *
                 * Continúa sujeto al flujo normal.
                 *
                 * Finalizado no puede establecerse manualmente
                 * por un operador.
                 */
                if (
                    $nuevoEstado ===
                    DenunciaEstado::FINALIZADO
                ) {
                    throw ValidationException::withMessages([
                        'estado' => [
                            'Una denuncia solo puede finalizarse mediante el proceso de verificación de resolución.',
                        ],
                    ]);
                }

                if (
                    !$denunciaBloqueada
                        ->puedeCambiarEstadoA(
                            $nuevoEstado
                        )
                ) {
                    throw ValidationException::withMessages([
                        'estado' => [
                            sprintf(
                                'No se puede cambiar una denuncia de "%s" a "%s".',
                                $estadoActual->label(),
                                $nuevoEstado->label()
                            ),
                        ],
                    ]);
                }

                /*
                 * Si un operador rechaza una denuncia,
                 * cerramos cualquier asignación activa.
                 */
                if (
                    $nuevoEstado ===
                    DenunciaEstado::RECHAZADO
                ) {
                    $denunciaBloqueada
                        ->asignaciones()
                        ->whereIn(
                            'estado_asignacion',
                            [
                                'pendiente',
                                'en_proceso',
                            ]
                        )
                        ->update([
                            'estado_asignacion' =>
                                'rechazado',

                            'comentario_cierre' =>
                                'Asignación cerrada porque la denuncia fue rechazada.',
                        ]);
                }

                $denunciaBloqueada
                    ->cambiarEstado(
                        $nuevoEstado
                    );

                return [
                    'sin_cambios' => false,
                    'estado' => $nuevoEstado,
                ];
            }
        );

        if ($resultado['sin_cambios']) {
            return back()->with(
                'success',
                'La denuncia ya se encuentra en ese estado.'
            );
        }

        return back()->with(
            'success',
            sprintf(
                'Estado actualizado a "%s" correctamente.',
                $resultado['estado']->label()
            )
        );
    }

    /**
     * Una denuncia puede recibir una asignación únicamente
     * mientras se encuentre pendiente o en proceso.
     */
    private function validarDenunciaAsignable(
        Denuncia $denuncia
    ): void {
        if (!$denuncia->municipio_id) {
            throw ValidationException::withMessages([
                'municipio' => [
                    'La denuncia no tiene un municipio asignado.',
                ],
            ]);
        }

        $estadosAsignables = [
            DenunciaEstado::PENDIENTE,
            DenunciaEstado::EN_PROCESO,
        ];

        if (
            !in_array(
                $denuncia->estadoEnum(),
                $estadosAsignables,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'estado' => [
                    sprintf(
                        'No puede asignarse un camión a una denuncia que se encuentra en estado "%s".',
                        $denuncia->estadoLabel()
                    ),
                ],
            ]);
        }
    }

    /**
     * Valida las reglas de negocio del camión seleccionado.
     */
    private function validarCamionParaDenuncia(
        Camion $camion,
        Denuncia $denuncia
    ): void {
        if (
            $camion->estado_operativo
            !== 'activo'
        ) {
            throw ValidationException::withMessages([
                'camion_id' => [
                    'El camión seleccionado no se encuentra activo.',
                ],
            ]);
        }

        if (
            (int) $camion->municipio_id
            !==
            (int) $denuncia->municipio_id
        ) {
            throw ValidationException::withMessages([
                'camion_id' => [
                    'El camión seleccionado no pertenece al mismo municipio de la denuncia.',
                ],
            ]);
        }
    }

    /**
     * Cierra cualquier asignación activa anterior
     * y crea la nueva asignación.
     *
     * Este método debe ejecutarse siempre dentro
     * de una transacción.
     */
    private function reemplazarAsignacionActiva(
        Denuncia $denuncia,
        Camion $camion
    ): void {
        $denuncia
            ->asignaciones()
            ->whereIn(
                'estado_asignacion',
                [
                    'pendiente',
                    'en_proceso',
                ]
            )
            ->update([
                'estado_asignacion' =>
                    'rechazado',
            ]);

        /*
         * No usamos create([...]) porque camion_id,
         * denuncia_id y estado_asignacion son
         * campos internos protegidos contra
         * mass assignment.
         */
        $asignacion =
            new DenunciaCamion();

        $asignacion->camion_id =
            $camion->id;

        $asignacion->estado_asignacion =
            'pendiente';

        /*
         * save() mediante la relación establece
         * denuncia_id de forma controlada.
         */
        $denuncia
            ->asignaciones()
            ->save(
                $asignacion
            );
    }
}