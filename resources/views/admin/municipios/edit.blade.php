@extends('layouts.admin')

@section('title', 'Editar municipio')

@section('content')

<div class="max-w-xl mx-auto bg-white shadow rounded p-6">

    <h1 class="text-2xl font-bold tema-titulo mb-4">
        Editar municipio
    </h1>

    <form
        method="POST"
        action="{{ route('admin.municipios.update', $municipio) }}"
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
                value="{{ old('nombre', $municipio->nombre) }}"
                required
                maxlength="255"
                class="w-full border rounded px-3 py-2"
            >

            @error('nombre')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex gap-3">
            <button
                type="submit"
                class="tema-boton px-4 py-2 rounded "
            >
                Guardar cambios
            </button>

            <a
                href="{{ route('admin.municipios.index') }}"
                class="border px-4 py-2 rounded hover:bg-gray-50"
            >
                Cancelar
            </a>
        </div>
    </form>

</div>

@endsection
