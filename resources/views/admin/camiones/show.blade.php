@extends('layouts.admin')

@section('title', 'Camión #'.$camion->id)

@section('content')

<div class="bg-white shadow rounded p-6">

    <div class="flex justify-between items-start mb-6">

        <h1 class="text-2xl font-bold tema-titulo">
            Camión #{{ $camion->id }}
        </h1>

        <a
            href="{{ route('admin.camiones.index') }}"
            class="tema-enlace hover:underline"
        >
            ← Volver
        </a>

    </div>

    <div class="grid md:grid-cols-2 gap-6 mb-8">

        <div>

            <p>
                <strong>Matrícula:</strong>
                {{ $camion->matricula }}
            </p>

            <p>
                <strong>Marca:</strong>
                {{ $camion->marca }}
            </p>

            <p>
                <strong>Modelo:</strong>
                {{ $camion->modelo }}
            </p>

            <p>
                <strong>Estado operativo:</strong>

                {{ $camion->estado_operativo === 'activo'
                    ? 'Activo'
                    : 'Inactivo'
                }}
            </p>

            <p>
                <strong>Municipio:</strong>
                {{ $camion->municipio->nombre ?? 'Sin municipio' }}
            </p>

            <p>
                <strong>Asignación activa:</strong>
                {{ $camion->asignado ? 'Sí' : 'No' }}
            </p>

        </div>

        <div>

            <div class="tema-suave border border-gray-200 rounded p-4">

                <h2 class="text-lg font-semibold tema-titulo mb-2">
                    Historial de asignaciones
                </h2>

                <p class="text-3xl font-bold">
                    {{ $camion->asignaciones->count() }}
                </p>

            </div>

        </div>

    </div>

    <div>

        <h2 class="text-xl font-semibold mb-4">
            Listado de denuncias asignadas
        </h2>

        @if($camion->asignaciones->isEmpty())

            <p class="text-gray-500">
                No hay denuncias asignadas a este camión.
            </p>

        @else

            <div class="bg-white shadow overflow-hidden rounded">

                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="tema-suave">

                        <tr>

                            <th class="px-4 py-3 text-left text-xs font-medium uppercase">
                                Denuncia
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-medium uppercase">
                                Estado asignación
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-medium uppercase">
                                Acciones
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-200">

                        @foreach(
                            $camion->asignaciones
                            as $asignacion
                        )

                            <tr>

                                <td class="px-4 py-2">

                                    @if($asignacion->denuncia)

                                        #{{ $asignacion->denuncia->id }}
                                        -
                                        {{ $asignacion->denuncia->direccion }}

                                    @else

                                        Denuncia eliminada

                                    @endif

                                </td>

                                <td class="px-4 py-2">

                                    {{ match($asignacion->estado_asignacion) {
                                        'pendiente' => 'Pendiente',
                                        'en_proceso' => 'En proceso',
                                        'resuelto' => 'Resuelto',
                                        'rechazado' => 'Rechazado',
                                        default => $asignacion->estado_asignacion,
                                    } }}

                                </td>

                                <td class="px-4 py-2">

                                    @if($asignacion->denuncia)

                                        <a
                                            href="{{ route('admin.denuncias.show', $asignacion->denuncia) }}"
                                            class="tema-enlace hover:underline"
                                        >
                                            Ver denuncia
                                        </a>

                                    @else

                                        <span class="text-gray-400">
                                            No disponible
                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @endif

    </div>

</div>

@endsection