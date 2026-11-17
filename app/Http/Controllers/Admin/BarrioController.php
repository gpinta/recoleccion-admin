<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBarrioRequest;
use App\Http\Requests\Admin\UpdateBarrioRequest;
use App\Models\Barrio;
use App\Models\Municipio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BarrioController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Barrio::class);

        $barrios = Barrio::query()
            ->with('municipio')
            ->withCount('denuncias')
            ->orderBy('nombre')
            ->orderBy('id')
            ->paginate(15);

        $municipios = Municipio::orderBy('nombre')->get();

        return view(
            'admin.barrios.index',
            compact('barrios', 'municipios')
        );
    }

    public function store(
        StoreBarrioRequest $request
    ) {
        Gate::authorize('create', Barrio::class);

        $validated = $request->validated();

        $existe = Barrio::where(
            'municipio_id',
            $validated['municipio_id']
        )
            ->where(
                'nombre',
                $validated['nombre']
            )
            ->exists();

        if ($existe) {
            return back()
                ->withErrors([
                    'nombre' =>
                        'Ese barrio ya existe en el municipio seleccionado.',
                ])
                ->withInput();
        }

        Barrio::create([
            'municipio_id' =>
                $validated['municipio_id'],

            'nombre' =>
                $validated['nombre'],
        ]);

        return back()->with(
            'success',
            'Barrio creado correctamente.'
        );
    }

    public function edit(
        Barrio $barrio
    ) {
        Gate::authorize(
            'update',
            $barrio
        );

        $barrio->load('municipio');

        return view(
            'admin.barrios.edit',
            compact('barrio')
        );
    }

    public function update(
        UpdateBarrioRequest $request,
        Barrio $barrio
    ) {
        Gate::authorize(
            'update',
            $barrio
        );

        $validated =
            $request->validated();

        /*
         * La edición administrativa modifica solamente
         * el nombre. La asociación con municipio continúa
         * siendo una operación independiente disponible
         * para operators y admins.
         */
        $barrio->nombre =
            $validated['nombre'];

        $barrio->save();

        return redirect()
            ->route(
                'admin.barrios.index'
            )
            ->with(
                'success',
                'Barrio actualizado correctamente.'
            );
    }

    public function asignarMunicipio(
        Request $request,
        Barrio $barrio
    ) {
        Gate::authorize(
            'assignMunicipio',
            $barrio
        );

        $validated = $request->validate([
            'municipio_id' => [
                'required',
                'integer',
                'exists:municipios,id',
            ],
        ]);

        $barrio->municipio_id =
            $validated['municipio_id'];

        $barrio->save();

        /*
         * Las denuncias existentes conservan el municipio
         * que tenían al momento de ser creadas.
         *
         * La reasignación de una denuncia individual se
         * realiza desde su edición administrativa para no
         * modificar masivamente datos históricos.
         */

        return back()->with(
            'success',
            'Municipio del barrio actualizado correctamente.'
        );
    }

    public function destroy(
        Barrio $barrio
    ) {
        Gate::authorize(
            'delete',
            $barrio
        );

        if (
            $barrio
                ->denuncias()
                ->exists()
        ) {
            return back()->withErrors([
                'barrio' =>
                    'No se puede eliminar el barrio porque existen denuncias asociadas.',
            ]);
        }

        $barrio->delete();

        return back()->with(
            'success',
            'Barrio eliminado correctamente.'
        );
    }
}
