@extends('layouts.admin')
@section('title', 'Municipios')
@section('content')

<h1 class="text-2xl font-bold tema-titulo mb-4">
    Municipios
</h1>

<div class="grid md:grid-cols-2 gap-4">

    @can('create', App\Models\Municipio::class)
    <div class="bg-white shadow rounded p-4">

        <h2 class="text-lg font-semibold mb-3">
            Crear municipio
        </h2>

        <form
            method="POST"
            action="{{ route('admin.municipios.store') }}"
        >

            @csrf

            <div class="mb-3">

                <label class="block">
                    Nombre
                </label>

                <input
                    type="text"
                    name="nombre"
                    value="{{ old('nombre') }}"
                    required
                    maxlength="255"
                    class="w-full border rounded px-3 py-2"
                >

            </div>

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

                    <th class="text-left">
                        Nombre
                    </th>

                    <th class="text-left">
                        Barrios
                    </th>

                    <th class="text-left">
                        Denuncias
                    </th>

                    <th class="text-left">
                        Camiones
                    </th>

                    <th></th>

                </tr>

            </thead>

            <tbody>

                @forelse($municipios as $municipio)

                    <tr>

                        <td>
                            {{ $municipio->nombre }}
                        </td>

                        <td>
                            {{ $municipio->barrios_count }}
                        </td>

                        <td>
                            {{ $municipio->denuncias_count }}
                        </td>

                        <td>
                            {{ $municipio->camiones_count }}
                        </td>

                        <td>

                            @can('update', $municipio)
                                <a
                                    href="{{ route('admin.municipios.edit', $municipio) }}"
                                    class="text-amber-600 hover:underline mr-3"
                                >
                                    Editar
                                </a>
                            @endcan

                            @if(
                                $municipio->barrios_count === 0
                                && $municipio->denuncias_count === 0
                                && $municipio->camiones_count === 0
                            )

                                @can('delete', $municipio)
                                <form
                                    method="POST"
                                    action="{{ route('admin.municipios.destroy', $municipio) }}"
                                    onsubmit="return confirm('¿Eliminar este municipio?')"
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

                            @else

                                <span
                                    class="text-gray-400 text-sm"
                                    title="El municipio posee elementos asociados"
                                >
                                    En uso
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="5"
                            class="py-4 text-center text-gray-500"
                        >
                            No hay municipios registrados.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

        @if($municipios->hasPages())
            <div class="mt-4 border-t pt-4">
                {{ $municipios->links() }}
            </div>
        @endif

    </div>

</div>
@endsection