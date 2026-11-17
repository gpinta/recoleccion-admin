@extends('layouts.admin')
@section('title', 'Barrios')
@section('content')

<h1 class="text-2xl font-bold tema-titulo mb-4">
    Barrios
</h1>

@can('create', App\Models\Barrio::class)
    <div class="bg-white shadow rounded p-4 mb-6">
        <h2 class="text-lg font-semibold mb-3">
            Crear barrio
        </h2>

        <form
            method="POST"
            action="{{ route('admin.barrios.store') }}"
            class="grid md:grid-cols-3 gap-3"
        >
            @csrf

            <select
                name="municipio_id"
                required
                class="border rounded px-3 py-2"
            >
                @foreach($municipios as $municipio)
                    <option
                        value="{{ $municipio->id }}"
                        {{ old('municipio_id') == $municipio->id ? 'selected' : '' }}
                    >
                        {{ $municipio->nombre }}
                    </option>
                @endforeach
            </select>

            <input
                type="text"
                name="nombre"
                value="{{ old('nombre') }}"
                required
                maxlength="255"
                placeholder="Nombre del barrio"
                class="border rounded px-3 py-2"
            >

            <button
                type="submit"
                class="tema-boton px-4 py-2 rounded "
            >
                Crear
            </button>
        </form>
    </div>
@endcan

<div class="bg-white shadow rounded p-4">
    <h2 class="text-lg font-semibold mb-3">
        Listado
    </h2>

    <table class="w-full">
        <thead>
            <tr>
                <th class="text-left">Nombre</th>
                <th class="text-left">Municipio</th>
                <th class="text-left">Denuncias</th>
                <th class="text-left">Acciones</th>
            </tr>
        </thead>

        <tbody>
            @forelse($barrios as $barrio)
                <tr class="border-t">
                    <td class="py-2">
                        {{ $barrio->nombre }}
                    </td>

                    <td class="py-2">
                        @can('assignMunicipio', $barrio)
                            <form
                                method="POST"
                                action="{{ route('admin.barrios.asignarMunicipio', $barrio) }}"
                                class="flex gap-2"
                            >
                                @csrf
                                @method('PATCH')

                                <select
                                    name="municipio_id"
                                    class="border rounded px-2 py-1"
                                    required
                                >
                                    @foreach($municipios as $municipio)
                                        <option
                                            value="{{ $municipio->id }}"
                                            {{ (int) $barrio->municipio_id === (int) $municipio->id ? 'selected' : '' }}
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
                            {{ $barrio->municipio->nombre ?? '—' }}
                        @endcan
                    </td>

                    <td class="py-2">
                        {{ $barrio->denuncias_count }}
                    </td>

                    <td class="py-2">
                        @can('update', $barrio)
                            <a
                                href="{{ route('admin.barrios.edit', $barrio) }}"
                                class="text-amber-600 hover:underline mr-3"
                            >
                                Editar
                            </a>
                        @endcan

                        @can('delete', $barrio)
                            @if($barrio->denuncias_count === 0)
                                <form
                                    method="POST"
                                    action="{{ route('admin.barrios.destroy', $barrio) }}"
                                    onsubmit="return confirm('¿Eliminar este barrio?')"
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
                            @else
                                <span class="text-gray-400 text-sm">
                                    En uso
                                </span>
                            @endif
                        @else
                            <span class="text-gray-400 text-sm">
                                Solo lectura
                            </span>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td
                        colspan="4"
                        class="py-4 text-center text-gray-500"
                    >
                        No hay barrios registrados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($barrios->hasPages())
        <div class="mt-4 border-t pt-4">
            {{ $barrios->links() }}
        </div>
    @endif
</div>
@endsection