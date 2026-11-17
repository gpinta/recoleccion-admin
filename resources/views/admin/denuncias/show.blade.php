@extends('layouts.admin')

@section('title', 'Denuncia #'.$denuncia->id)

@section('content')

<div class="bg-white shadow rounded p-6">

    <div class="flex justify-between items-start mb-6">

        <h1 class="text-2xl font-bold tema-titulo">
            Denuncia #{{ $denuncia->id }}
        </h1>

        <a
            href="{{ route('admin.denuncias.index') }}"
            class="tema-enlace hover:underline"
        >
            ← Volver
        </a>

    </div>

    {{-- Información general --}}
    <div class="grid md:grid-cols-2 gap-6">

        <div>

            <p>
                <strong>Ciudadano:</strong>
                {{ $denuncia->user->name ?? 'Usuario eliminado' }}
            </p>

            <p>
                <strong>Documento:</strong>
                {{ $denuncia->user->documento ?? '—' }}
            </p>

            <p>
                <strong>Email:</strong>
                {{ $denuncia->user->email ?? '—' }}
            </p>

            <p>
                <strong>Dirección:</strong>
                {{ $denuncia->direccion }}
            </p>

            <p>
                <strong>Barrio:</strong>
                {{ $denuncia->barrio->nombre ?? '—' }}
            </p>

            <p>
                <strong>Municipio:</strong>
                {{ $denuncia->municipio->nombre ?? '—' }}
            </p>

            <p>
                <strong>Descripción:</strong>
                {{ $denuncia->descripcion ?? 'Sin descripción' }}
            </p>

            <p>
                <strong>Estado actual:</strong>
                {{ $denuncia->estadoLabel() }}
            </p>

        </div>

        <div>

            <div
                id="map"
                style="height: 250px;"
                class="rounded border"
            ></div>

        </div>

    </div>

    {{-- Fotos ciudadanas privadas --}}
    <div class="mt-6">

        <h2 class="text-lg font-semibold mb-2">
            Fotos
        </h2>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">

            @forelse($denuncia->fotos as $foto)

                @php
                    $fotoUrl = route(
                        'admin.denuncias.fotos.show',
                        [
                            'denuncia' => $denuncia->id,
                            'foto' => $foto->id,
                        ]
                    );
                @endphp

                <img
                    src="{{ $fotoUrl }}"
                    alt="Foto de la denuncia"
                    class="rounded border object-cover w-full h-32 cursor-pointer hover:opacity-80 transition"
                    data-full-img="{{ $fotoUrl }}"
                    onclick="openImageModal(this)"
                >

            @empty

                <p class="text-gray-500">
                    Sin fotos.
                </p>

            @endforelse

        </div>

    </div>

    @if($denuncia->estado === 'reclamado')
        <div class="mt-6 border border-red-300 bg-red-50 rounded" style="padding: 16px;">
            <strong class="text-red-800">Denuncia reclamada</strong>
            <p class="text-sm text-red-700 mt-1">
                Debe ser revisada. El operador puede reabrirla
                como Pendiente o En proceso, o rechazar la denuncia.
            </p>
        </div>
    @endif

    @can('updateEstado', $denuncia)

    {{-- Cambio manual de estado --}}
    <div class="mt-8 border-t pt-6">

        <h2 class="text-xl font-semibold mb-4">
            Cambiar estado
        </h2>

        @php

            $usuarioBackend =
                auth()->user();

            $esAdminBackend =
                $usuarioBackend
                && $usuarioBackend
                    ->esBackendAdmin();

            /*
             * Admin:
             * puede seleccionar cualquier estado.
             *
             * Operator:
             * solo puede seleccionar el estado actual y las
             * transiciones normales permitidas. Finalizado
             * queda reservado al proceso de verificación.
             */
            if ($esAdminBackend) {
                $estadosDisponibles =
                    \App\Enums\DenunciaEstado::cases();
            } else {
                $estadoActual =
                    $denuncia->estadoEnum();

                $estadosDisponibles =
                    collect([
                        $estadoActual,
                        ...$estadoActual
                            ->transicionesPermitidas(),
                    ])
                        ->reject(
                            fn ($estado) =>
                                in_array(
                                    $estado,
                                    [
                                        \App\Enums\DenunciaEstado::FINALIZADO,
                                        \App\Enums\DenunciaEstado::RECLAMADO,
                                    ],
                                    true
                                )
                        )
                        ->unique(
                            fn ($estado) =>
                                $estado->value
                        )
                        ->values()
                        ->all();
            }

        @endphp

        <form
            method="POST"
            action="{{ route('admin.denuncias.updateEstado', $denuncia) }}"
            class="flex flex-wrap gap-4"
        >

            @csrf
            @method('PATCH')

            <select
                name="estado"
                class="border rounded px-3 py-2"
            >

                @foreach(
                    $estadosDisponibles
                    as $estadoDisponible
                )

                    <option
                        value="{{ $estadoDisponible->value }}"
                        {{
                            $denuncia->estado ===
                            $estadoDisponible->value
                                ? 'selected'
                                : ''
                        }}
                    >
                        {{ $estadoDisponible->label() }}
                    </option>

                @endforeach

            </select>

            <button
                type="submit"
                class="tema-boton px-4 py-2 rounded "
            >
                Actualizar
            </button>

        </form>

        @if($esAdminBackend)

            <p class="text-sm text-amber-700 mt-3">
                Como administrador puedes corregir la denuncia
                desde cualquier estado hacia cualquier otro estado.
            </p>

        @endif

    </div>

    @endcan

    {{-- Asignación a camión --}}
    <div class="mt-8 border-t pt-6">

        <h2 class="text-xl font-semibold mb-4">
            Asignación a camión
        </h2>

        @php

            $asignacionActiva = $denuncia
                ->asignaciones
                ->whereIn(
                    'estado_asignacion',
                    [
                        'pendiente',
                        'en_proceso',
                    ]
                )
                ->first();

            $ultimaResolucion = $denuncia
                ->asignaciones
                ->where(
                    'estado_asignacion',
                    'resuelto'
                )
                ->last();

        @endphp

        @if($asignacionActiva)

            <p class="mb-2">

                <strong>Camión asignado:</strong>

                {{ $asignacionActiva->camion->matricula }}

                (
                    {{ $asignacionActiva->camion->marca }}
                    {{ $asignacionActiva->camion->modelo }}
                )

            </p>

            <p class="mb-4">

                <strong>Estado asignación:</strong>
                {{ $asignacionActiva->estado_asignacion }}

            </p>

        @else

            <p class="mb-4 text-gray-600">
                Esta denuncia no tiene un camión asignado activo.
            </p>

        @endif

        @can('asignar', $denuncia)
        <div class="flex flex-wrap gap-3">

            {{-- Asignación automática --}}
            <form
                method="POST"
                action="{{ route('admin.denuncias.asignarAutomatico', $denuncia) }}"
            >

                @csrf

                <button
                    type="submit"
                    class="tema-boton px-4 py-2 rounded "
                >
                    Asignar automáticamente
                </button>

            </form>

            {{-- Asignación manual --}}
            <form
                method="POST"
                action="{{ route('admin.denuncias.asignarManual', $denuncia) }}"
                class="flex gap-2"
            >

                @csrf

                <select
                    name="camion_id"
                    class="border rounded px-3 py-2"
                    required
                >

                    <option value="">
                        Seleccionar camión...
                    </option>

                    @foreach($camiones as $camion)

                        <option value="{{ $camion->id }}">

                            {{ $camion->matricula }}
                            -
                            {{ $camion->municipio->nombre ?? 'Sin municipio' }}

                        </option>

                    @endforeach

                </select>

                <button
                    type="submit"
                    class="tema-boton px-4 py-2 rounded "
                >
                    Asignar manualmente
                </button>

            </form>

            @if($asignacionActiva)

                <form
                    method="POST"
                    action="{{ route('admin.denuncias.desasignar', $denuncia) }}"
                    onsubmit="return confirm('¿Desasignar el camión de esta denuncia?')"
                >
                    @csrf

                    <button
                        type="submit"
                        class="tema-boton px-4 py-2 rounded "
                    >
                        Desasignar
                    </button>
                </form>

            @endif

        </div>

        @endcan

        {{-- Evidencia de resolución de la Aplicación Camiones --}}
        @if(
            $ultimaResolucion
            && $ultimaResolucion->fotos->isNotEmpty()
        )

            <div class="mt-6">

                <h3 class="text-lg font-semibold mb-2">
                    Evidencia de resolución
                </h3>

                <p>

                    <strong>Comentario del camión:</strong>

                    {{
                        $ultimaResolucion->comentario_cierre
                        ?? 'Sin comentario'
                    }}

                </p>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">

                    @foreach(
                        $ultimaResolucion->fotos
                        as $foto
                    )

                        @php
                            /*
                             * Fotografía privada de resolución.
                             *
                             * La URL apunta al controlador
                             * administrativo protegido.
                             *
                             * No utilizamos /storage/ porque
                             * las evidencias son privadas.
                             *
                             * El registro conserva
                             * denuncia_camion_id para auditoría.
                             */
                            $fotoResolucionUrl = route(
                                'admin.denuncias.resoluciones.fotos.show',
                                [
                                    'denuncia' => $denuncia->id,
                                    'asignacion' => $ultimaResolucion->id,
                                    'foto' => $foto->id,
                                ]
                            );
                        @endphp

                        <img
                            src="{{ $fotoResolucionUrl }}"
                            alt="Evidencia"
                            class="rounded border object-cover w-full h-32 cursor-pointer hover:opacity-80 transition"
                            data-full-img="{{ $fotoResolucionUrl }}"
                            onclick="openImageModal(this)"
                        >

                    @endforeach

                </div>

                @if($denuncia->estado === 'resuelto')
                @can('verificarResolucion', $denuncia)
                <form
                    method="POST"
                    action="{{ route('admin.denuncias.verificarResolucion', $denuncia) }}"
                    class="mt-3"
                >

                    @csrf

                    <button
                        type="submit"
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
                    >
                        Confirmar resolución
                    </button>

                </form>
                @endcan
                @endif

            </div>

        @endif

    </div>

    {{-- Reclamos del ciudadano --}}
    <div class="mt-8 border-t pt-6">

        <div class="flex items-center justify-between mb-4">

            <h2 class="text-xl font-semibold">
                Reclamos
            </h2>

            @if(
                $denuncia
                    ->reclamaciones
                    ->isNotEmpty()
            )

                <span
                    class="bg-red-100 text-red-700 text-sm font-semibold px-3 py-1 rounded-full"
                >

                    {{ $denuncia->reclamaciones->count() }}

                    {{
                        $denuncia->reclamaciones->count() === 1
                            ? 'reclamo'
                            : 'reclamos'
                    }}

                </span>

            @endif

        </div>

        @forelse(
            $denuncia
                ->reclamaciones
                ->sortByDesc('created_at')
            as $reclamacion
        )

            <div
                class="border border-red-200 bg-red-50 rounded-lg p-4 mb-3"
            >

                <div
                    class="flex flex-col md:flex-row md:justify-between md:items-center gap-1 mb-2"
                >

                    <div>

                        <span class="font-semibold text-gray-800">

                            {{
                                $reclamacion->user->name
                                ?? 'Usuario eliminado'
                            }}

                        </span>

                        @if($reclamacion->user)

                            <span class="text-sm text-gray-500">

                                —
                                Documento:
                                {{ $reclamacion->user->documento }}

                            </span>

                        @endif

                    </div>

                    <span class="text-sm text-gray-500">

                        {{
                            $reclamacion
                                ->created_at
                                ->format('d/m/Y H:i')
                        }}

                    </span>

                </div>

                <p class="text-gray-700 whitespace-pre-line">
                    {{ $reclamacion->comentario }}
                </p>

            </div>

        @empty

            <p class="text-gray-500">
                Esta denuncia no posee reclamos.
            </p>

        @endforelse

    </div>

</div>

{{-- Scripts: mapa y modal --}}
<script>

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const map = L
                .map('map')
                .setView(
                    [
                        {{ $denuncia->latitud }},
                        {{ $denuncia->longitud }}
                    ],
                    16
                );

            L.tileLayer(
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                {
                    attribution:
                        '&copy; OpenStreetMap contributors'
                }
            ).addTo(map);

            L.marker(
                [
                    {{ $denuncia->latitud }},
                    {{ $denuncia->longitud }}
                ]
            ).addTo(map);
        }
    );

    function openImageModal(element) {

        const src =
            element.getAttribute(
                'data-full-img'
            );

        const existing =
            document.getElementById(
                'imageModal'
            );

        if (existing) {
            existing.remove();
        }

        const modal =
            document.createElement('div');

        modal.id =
            'imageModal';

        modal.style.cssText =
            'position:fixed;' +
            'top:0;' +
            'left:0;' +
            'width:100%;' +
            'height:100%;' +
            'z-index:9999;' +
            'display:flex;' +
            'align-items:center;' +
            'justify-content:center;' +
            'background:rgba(0,0,0,0.85);';

        const closeBtn =
            document.createElement('span');

        closeBtn.innerHTML =
            '&times;';

        closeBtn.style.cssText =
            'position:absolute;' +
            'top:15px;' +
            'right:20px;' +
            'color:white;' +
            'font-size:2.5rem;' +
            'cursor:pointer;' +
            'font-weight:bold;' +
            'line-height:1;' +
            'z-index:10000;';

        closeBtn.onclick =
            closeImageModal;

        const imgContainer =
            document.createElement('div');

        imgContainer.style.cssText =
            'max-width:90%;' +
            'max-height:90%;' +
            'display:flex;' +
            'align-items:center;' +
            'justify-content:center;';

        const img =
            document.createElement('img');

        img.src =
            src;

        img.alt =
            'Imagen ampliada';

        img.style.cssText =
            'max-width:100%;' +
            'max-height:90vh;' +
            'object-fit:contain;' +
            'border-radius:0.5rem;' +
            'box-shadow:0 0 30px rgba(0,0,0,0.5);';

        imgContainer.appendChild(
            img
        );

        modal.appendChild(
            closeBtn
        );

        modal.appendChild(
            imgContainer
        );

        modal.addEventListener(
            'click',
            function (event) {

                if (
                    event.target === modal
                ) {
                    closeImageModal();
                }
            }
        );

        document.body.appendChild(
            modal
        );

        document.body.style.overflow =
            'hidden';
    }

    function closeImageModal() {

        const modal =
            document.getElementById(
                'imageModal'
            );

        if (modal) {
            modal.remove();
        }

        document.body.style.overflow =
            '';
    }

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape'
                && document.getElementById(
                    'imageModal'
                )
            ) {
                closeImageModal();
            }
        }
    );

</script>

@endsection