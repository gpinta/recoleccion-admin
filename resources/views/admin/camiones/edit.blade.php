@extends('layouts.admin')
@section('title', 'Editar Camión')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold tema-titulo mb-4">Editar Camión #{{ $camion->id }}</h1>
    <div class="bg-white shadow rounded p-6">
        <form method="POST" action="{{ route('admin.camiones.update', $camion) }}">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-gray-700" for="matricula">Matrícula</label>
                    <input type="text" id="matricula" name="matricula" value="{{ old('matricula', $camion->matricula) }}" class="w-full border rounded px-3 py-2 mt-1" required>
                    @error('matricula')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700" for="marca">Marca</label>
                    <input type="text" id="marca" name="marca" value="{{ old('marca', $camion->marca) }}" class="w-full border rounded px-3 py-2 mt-1" required>
                    @error('marca')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700" for="modelo">Modelo</label>
                    <input type="text" id="modelo" name="modelo" value="{{ old('modelo', $camion->modelo) }}" class="w-full border rounded px-3 py-2 mt-1" required>
                    @error('modelo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700" for="estado_operativo">Estado operativo</label>
                    <select id="estado_operativo" name="estado_operativo" class="w-full border rounded px-3 py-2 mt-1" required>
                        <option value="activo" {{ old('estado_operativo', $camion->estado_operativo) == 'activo' ? 'selected' : '' }}>Activo</option>
                        <option value="inactivo" {{ old('estado_operativo', $camion->estado_operativo) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                    @error('estado_operativo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700" for="municipio_id">Municipio asignado</label>
                    <select id="municipio_id" name="municipio_id" class="w-full border rounded px-3 py-2 mt-1">
                        <option value="">Sin asignar</option>
                        @foreach($municipios as $municipio)
                            <option value="{{ $municipio->id }}" {{ old('municipio_id', $camion->municipio_id) == $municipio->id ? 'selected' : '' }}>{{ $municipio->nombre }}</option>
                        @endforeach
                    </select>
                    @error('municipio_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Cambio de contraseña de la Aplicación Camiones --}}
            <div class="border-t border-gray-200 pt-4 mt-4">
                <h2 class="text-lg font-semibold tema-titulo mb-2">
                    Cambiar contraseña
                </h2>

                <p class="text-sm text-gray-600 mb-4">
                    El usuario de acceso es la matrícula del camión. Dejá ambos campos vacíos para conservar la contraseña actual.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700" for="password">Nueva contraseña</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="w-full border rounded px-3 py-2 mt-1"
                            autocomplete="new-password"
                        >
                        @error('password')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700" for="password_confirmation">Confirmar nueva contraseña</label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="w-full border rounded px-3 py-2 mt-1"
                            autocomplete="new-password"
                        >
                    </div>
                </div>

                <p class="text-sm text-gray-500">
                    La nueva contraseña debe tener al menos 8 caracteres, con mayúscula, minúscula, número y carácter especial permitido: @ . - _ $ !
                </p>
            </div>

            <div class="flex gap-3 mt-4">
                <button type="submit" class="tema-boton px-6 py-2 rounded ">Actualizar</button>
                <a href="{{ route('admin.camiones.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection