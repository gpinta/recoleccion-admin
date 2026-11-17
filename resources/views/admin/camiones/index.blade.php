@extends('layouts.admin')

@section('title', 'Camiones')

@section('content')

<h1 class="text-2xl font-bold tema-titulo mb-4">
    Camiones
</h1>

<div class="flex flex-col md:flex-row gap-4 mb-6">
    <form
        method="GET"
        class="bg-white p-4 rounded shadow flex flex-wrap gap-4 flex-1"
    >
        <input
            type="text"
            name="buscar"
            placeholder="Matrícula, marca o modelo"
            value="{{ request('buscar') }}"
            class="border rounded px-3 py-2"
        >

        <select
            name="estado_operativo"
            class="border rounded px-3 py-2"
        >
            <option value="">Todos los estados</option>
            <option value="activo" {{ request('estado_operativo') == 'activo' ? 'selected' : '' }}>Activo</option>
            <option value="inactivo" {{ request('estado_operativo') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
        </select>

        <select
            name="municipio_id"
            class="border rounded px-3 py-2"
        >
            <option value="">Todos los municipios</option>

            @foreach($municipios as $municipio)
                <option
                    value="{{ $municipio->id }}"
                    {{ request('municipio_id') == $municipio->id ? 'selected' : '' }}
                >
                    {{ $municipio->nombre }}
                </option>
            @endforeach
        </select>

        <button
            type="submit"
            class="tema-boton px-4 py-2 rounded "
        >
            Buscar
        </button>
    </form>

    @can('create', App\Models\Camion::class)
        <a
            href="{{ route('admin.camiones.create') }}"
            class="tema-boton px-4 py-2 rounded  self-start"
        >
            Nuevo camión
        </a>
    @endcan
</div>

<div class="bg-white shadow overflow-hidden rounded">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="tema-suave">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">Matrícula</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">Marca</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">Modelo</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">Estado</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">Municipio</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">Asignado</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">Acciones</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200">
            @forelse($camiones as $camion)
                <tr>
                    <td class="px-4 py-2">{{ $camion->id }}</td>
                    <td class="px-4 py-2">{{ $camion->matricula }}</td>
                    <td class="px-4 py-2">{{ $camion->marca }}</td>
                    <td class="px-4 py-2">{{ $camion->modelo }}</td>
                    <td class="px-4 py-2">
                        {{ $camion->estado_operativo === 'activo' ? 'Activo' : 'Inactivo' }}
                    </td>

                    <td class="px-4 py-2">
                        @can('assignMunicipio', $camion)
                            <form
                                method="POST"
                                action="{{ route('admin.camiones.asignarMunicipio', $camion) }}"
                                class="flex gap-2"
                            >
                                @csrf
                                @method('PATCH')

                                <select
                                    name="municipio_id"
                                    class="border rounded px-2 py-1"
                                >
                                    <option value="">Sin municipio</option>

                                    @foreach($municipios as $municipio)
                                        <option
                                            value="{{ $municipio->id }}"
                                            {{ (int) $camion->municipio_id === (int) $municipio->id ? 'selected' : '' }}
                                        >
                                            {{ $municipio->nombre }}
                                        </option>
                                    @endforeach
                                </select>

                                <button
                                    type="submit"
                                    class="tema-enlace hover:underline"
                                >
                                    Asignar
                                </button>
                            </form>
                        @else
                            {{ $camion->municipio->nombre ?? '—' }}
                        @endcan
                    </td>

                    <td class="px-4 py-2">
                        {{ $camion->asignado ? 'Sí' : 'No' }}
                    </td>

                    <td class="px-4 py-2 flex gap-2">
                        <a
                            href="{{ route('admin.camiones.show', $camion) }}"
                            class="tema-enlace hover:underline"
                        >
                            Ver
                        </a>

                        @can('update', $camion)
                            <a
                                href="{{ route('admin.camiones.edit', $camion) }}"
                                class="tema-enlace hover:underline"
                            >
                                Editar
                            </a>
                        @endcan

                        @can('delete', $camion)
                            <form
                                action="{{ route('admin.camiones.destroy', $camion) }}"
                                method="POST"
                                onsubmit="return confirm('¿Eliminar camión?')"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="text-red-600 hover:underline"
                                >
                                    Eliminar
                                </button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td
                        colspan="8"
                        class="px-4 py-4 text-center text-gray-500"
                    >
                        No hay camiones registrados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-4">
        {{ $camiones->links() }}
    </div>
</div>

@endsection
