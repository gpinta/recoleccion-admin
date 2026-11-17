@extends('layouts.admin')
@section('title', 'Iniciar sesión - Admin')

@section('content')
<div class="bg-white shadow-xl rounded-xl p-8 w-full max-w-md">
    <h1 class="text-2xl font-bold tema-titulo mb-6 text-center">Iniciar sesión</h1>
    <form method="POST" action="{{ route('admin.login') }}">
        @csrf
        <div class="mb-4">
            <label class="block text-gray-700">Usuario</label>
            <input type="text" name="username" value="{{ old('username') }}" class="w-full border border-gray-300 rounded px-3 py-2 mt-1 focus:outline-none focus:ring tema-input" required>
        </div>
        <div class="mb-6">
            <label class="block text-gray-700">Contraseña</label>
            <input type="password" name="password" class="w-full border border-gray-300 rounded px-3 py-2 mt-1 focus:outline-none focus:ring tema-input" required>
        </div>
        <button type="submit" class="w-full tema-boton py-2 rounded  transition">Ingresar</button>
    </form>
</div>
@endsection