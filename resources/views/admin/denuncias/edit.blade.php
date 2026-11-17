@extends('layouts.admin')

@section('title', 'Editar denuncia #'.$denuncia->id)

@section('content')

<div class="max-w-3xl mx-auto bg-white shadow rounded p-6">

    <div class="flex justify-between items-start mb-6">
        <h1 class="text-2xl font-bold tema-titulo">
            Editar denuncia #{{ $denuncia->id }}
        </h1>

        <a
            href="{{ route('admin.denuncias.show', $denuncia) }}"
            class="tema-enlace hover:underline"
        >
            ← Volver
        </a>
    </div>

    <form
        method="POST"
        action="{{ route('admin.denuncias.update', $denuncia) }}"
        class="space-y-4"
    >
        @csrf
        @method('PATCH')

        <div>
            <label class="block font-medium mb-1">
                Dirección
            </label>

            <input
                type="text"
                name="direccion"
                value="{{ old('direccion', $denuncia->direccion) }}"
                required
                maxlength="255"
                class="w-full border rounded px-3 py-2"
            >

            @error('direccion')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block font-medium mb-1">
                Barrio
            </label>

            <select
                name="barrio_id"
                required
                class="w-full border rounded px-3 py-2"
            >
                <option value="">Seleccionar barrio...</option>

                @foreach($barrios as $barrio)
                    <option
                        value="{{ $barrio->id }}"
                        {{ (string) old('barrio_id', $denuncia->barrio_id) === (string) $barrio->id ? 'selected' : '' }}
                    >
                        {{ $barrio->nombre }}
                        — {{ $barrio->municipio->nombre ?? 'Sin municipio' }}
                    </option>
                @endforeach
            </select>

            <p class="text-sm text-gray-500 mt-1">
                El municipio se actualizará automáticamente según el barrio seleccionado.
            </p>

            @error('barrio_id')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block font-medium mb-1">
                Descripción
            </label>

            <textarea
                name="descripcion"
                rows="5"
                maxlength="5000"
                class="w-full border rounded px-3 py-2"
            >{{ old('descripcion', $denuncia->descripcion) }}</textarea>

            @error('descripcion')
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
                href="{{ route('admin.denuncias.show', $denuncia) }}"
                class="border px-4 py-2 rounded hover:bg-gray-50"
            >
                Cancelar
            </a>
        </div>
    </form>

</div>

@endsection
