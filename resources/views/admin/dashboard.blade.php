@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<h1 class="text-2xl font-bold tema-titulo mb-6">Panel de Control</h1>

{{-- Bloque de denuncias --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white p-4 rounded shadow">
        <p class="text-sm text-gray-500">Pendientes</p>
        <p class="text-3xl">{{ $pendientes }}</p>
    </div>
    <div class="bg-white p-4 rounded shadow">
        <p class="text-sm text-gray-500">En proceso</p>
        <p class="text-3xl">{{ $enProceso }}</p>
    </div>
    <div class="bg-white p-4 rounded shadow">
        <p class="text-sm text-gray-500">Resueltas</p>
        <p class="text-3xl">{{ $resueltas }}</p>
    </div>
    <div class="bg-white p-4 rounded shadow">
        <p class="text-sm text-gray-500">Rechazadas</p>
        <p class="text-3xl">{{ $rechazadas }}</p>
    </div>
</div>

{{-- Bloque de camiones --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
    <div class="bg-white p-4 rounded shadow">
        <p class="text-sm text-gray-500">Total camiones</p>
        <p class="text-3xl font-bold">{{ $totalCamiones }}</p>
    </div>
    <div class="bg-white p-4 rounded shadow">
        <p class="text-sm text-gray-500">Camiones activos</p>
        <p class="text-3xl font-bold">{{ $camionesActivos }}</p>
    </div>
    <div class="bg-white p-4 rounded shadow">
        <p class="text-sm text-gray-500">Camiones inactivos</p>
        <p class="text-3xl font-bold">{{ $camionesInactivos }}</p>
    </div>
    <div class="bg-white p-4 rounded shadow">
        <p class="text-sm text-gray-500">Camiones asignados</p>
        <p class="text-3xl font-bold">{{ $camionesAsignados }}</p>
    </div>
</div>

{{-- Denuncias sin asignar --}}
<div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-xl p-6">
    <p class="text-sm text-yellow-700">Denuncias sin asignar</p>
    <p class="text-3xl font-bold text-yellow-800">{{ $denunciasSinAsignar }}</p>
</div>
@endsection