@extends('layouts.admin')
@section('title', 'Denuncias')
@section('content')
<h1 class="text-2xl font-bold tema-titulo mb-4">Denuncias</h1>
<form method="GET" class="mb-4 flex gap-4 bg-white p-4 rounded shadow">
    <select name="estado" class="border rounded px-3 py-2">
        <option value="">Todos</option>
        <option value="pendiente" {{ request('estado')=='pendiente'?'selected':'' }}>Pendiente</option>
        <option value="en_proceso" {{ request('estado')=='en_proceso'?'selected':'' }}>En proceso</option>
        <option value="resuelto" {{ request('estado')=='resuelto'?'selected':'' }}>Resuelto</option>
        <option value="reclamado" {{ request('estado')=='reclamado'?'selected':'' }}>Reclamado</option>
        <option value="finalizado" {{ request('estado')=='finalizado'?'selected':'' }}>Finalizado</option>
        <option value="rechazado" {{ request('estado')=='rechazado'?'selected':'' }}>Rechazado</option>
    </select>
    <input type="text" name="buscar" placeholder="N° denuncia" value="{{ request('buscar') }}" class="border rounded px-3 py-2">
    <button type="submit" class="tema-boton px-4 py-2 rounded ">Filtrar</button>
</form>
<div class="bg-white shadow overflow-hidden rounded">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="tema-suave">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">Ciudadano</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">Barrio</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">Dirección</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">Estado</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($denuncias as $denuncia)
            <tr @if($denuncia->estado === 'reclamado') style="background-color: #fff1f2;" @endif>
                <td class="px-4 py-2">{{ $denuncia->id }}</td>
                <td class="px-4 py-2">{{ $denuncia->user->name ?? 'Anónimo' }}</td>
                <td class="px-4 py-2">{{ $denuncia->barrio->nombre ?? '—' }}</td>
                <td class="px-4 py-2">{{ $denuncia->direccion }}</td>
                <td class="px-4 py-2">
                    @if($denuncia->estado === 'reclamado')
                        <span
                            class="inline-block rounded font-semibold text-red-800"
                            style="background-color: #ffe4e6; padding: 5px 10px;"
                        >
                            Reclamado — requiere atención
                        </span>
                    @else
                        <span class="capitalize">{{ $denuncia->estadoLabel() }}</span>
                    @endif
                </td>
                <td class="px-4 py-2">
                    <div class="flex flex-wrap gap-3">
                        <a
                            href="{{ route('admin.denuncias.show', $denuncia) }}"
                            class="tema-enlace hover:underline"
                        >
                            Ver
                        </a>

                        @can('update', $denuncia)
                            <a
                                href="{{ route('admin.denuncias.edit', $denuncia) }}"
                                class="text-amber-600 hover:underline"
                            >
                                Editar
                            </a>
                        @endcan

                        @can('delete', $denuncia)
                            <form
                                method="POST"
                                action="{{ route('admin.denuncias.destroy', $denuncia) }}"
                                onsubmit="return confirm('¿Eliminar definitivamente la denuncia #{{ $denuncia->id }}? Esta acción también eliminará sus reclamos y asignaciones de la base de datos.')"
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
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="p-4">{{ $denuncias->links() }}</div>
</div>
@endsection