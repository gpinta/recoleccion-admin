<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMunicipioRequest;
use App\Http\Requests\Admin\UpdateMunicipioRequest;
use App\Models\Municipio;
use Illuminate\Support\Facades\Gate;

class MunicipioController extends Controller
{
    public function index()
    {
        Gate::authorize(
            'viewAny',
            Municipio::class
        );

        $municipios =
            Municipio::query()
                ->withCount([
                    'barrios',
                    'denuncias',
                    'camiones',
                ])
                ->orderBy(
                    'nombre'
                )
                ->orderBy('id')
                ->paginate(15);

        return view(
            'admin.municipios.index',
            compact(
                'municipios'
            )
        );
    }

    public function store(
        StoreMunicipioRequest $request
    ) {
        Gate::authorize(
            'create',
            Municipio::class
        );

        $validated =
            $request->validated();

        Municipio::create([
            'nombre' =>
                $validated['nombre'],
        ]);

        return back()->with(
            'success',
            'Municipio creado correctamente.'
        );
    }

    public function edit(
        Municipio $municipio
    ) {
        Gate::authorize(
            'update',
            $municipio
        );

        return view(
            'admin.municipios.edit',
            compact('municipio')
        );
    }

    public function update(
        UpdateMunicipioRequest $request,
        Municipio $municipio
    ) {
        Gate::authorize(
            'update',
            $municipio
        );

        $validated =
            $request->validated();

        $municipio->nombre =
            $validated['nombre'];

        $municipio->save();

        return redirect()
            ->route(
                'admin.municipios.index'
            )
            ->with(
                'success',
                'Municipio actualizado correctamente.'
            );
    }

    public function destroy(
        Municipio $municipio
    ) {
        Gate::authorize(
            'delete',
            $municipio
        );

        /*
         * Primero protegemos los barrios porque la FK
         * actual utiliza cascadeOnDelete().
         *
         * Sin esta comprobación MySQL podría eliminar
         * automáticamente todos los barrios.
         */
        if (
            $municipio
                ->barrios()
                ->exists()
        ) {
            return back()->withErrors([
                'municipio' =>
                    'No se puede eliminar el municipio porque posee barrios asociados.',
            ]);
        }

        /*
         * Las denuncias constituyen información histórica
         * y operativa que no debe quedar desvinculada por
         * una eliminación administrativa accidental.
         */
        if (
            $municipio
                ->denuncias()
                ->exists()
        ) {
            return back()->withErrors([
                'municipio' =>
                    'No se puede eliminar el municipio porque existen denuncias asociadas.',
            ]);
        }

        /*
         * Tampoco permitimos eliminar un municipio
         * utilizado actualmente por camiones.
         */
        if (
            $municipio
                ->camiones()
                ->exists()
        ) {
            return back()->withErrors([
                'municipio' =>
                    'No se puede eliminar el municipio porque existen camiones asociados.',
            ]);
        }

        $municipio->delete();

        return back()->with(
            'success',
            'Municipio eliminado correctamente.'
        );
    }
}
