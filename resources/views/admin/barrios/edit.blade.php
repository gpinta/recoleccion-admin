@extends('layouts.admin')

@section('title', 'Editar barrio')

@section('content')

<div class="max-w-xl mx-auto bg-white shadow rounded p-6">

    <h1 class="text-2xl font-bold tema-titulo mb-4">
        Editar barrio
    </h1>

    <form
        method="POST"
        action="{{ route('admin.barrios.update', $barrio) }}"
        class="space-y-4"
    >
        @csrf
        @method('PUT')

        <div>
            <label class="block font-medium mb-1">
                Nombre
            </label>

            <input
                type="text"
                name="nombre"
                value="{{ old('nombre', $barrio->nombre) }}"
                required
                maxlength="255"
                class="w-full border rounded px-3 py-2"
            >

            @error('nombre')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <p class="text-sm text-gray-500">
            Municipio actual:
            <strong>{{ $barrio->municipio->nombre ?? 'Sin municipio' }}</strong>.
            La asociación con municipio se modifica desde el listado de barrios.
        </p>

        <div class="flex gap-3">
            <button
                type="submit"
                class="tema-boton px-4 py-2 rounded "
            >
                Guardar cambios
            </button>

            <a
                href="{{ route('admin.barrios.index') }}"
                class="border px-4 py-2 rounded hover:bg-gray-50"
            >
                Cancelar
            </a>
        </div>
    </form>

</div>

@endsection
