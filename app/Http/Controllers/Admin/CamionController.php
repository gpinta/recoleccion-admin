<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCamionRequest;
use App\Http\Requests\Admin\UpdateCamionRequest;
use App\Models\Camion;
use App\Models\CamionUser;
use App\Models\Municipio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CamionController extends Controller
{
    public function index(
        Request $request
    ) {
        Gate::authorize('viewAny', Camion::class);

        $query = Camion::query()
            ->with('municipio')
            ->withExists('asignacionesActivas');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(
                function ($query) use ($buscar) {
                    $query
                        ->where(
                            'matricula',
                            'like',
                            "%{$buscar}%"
                        )
                        ->orWhere(
                            'marca',
                            'like',
                            "%{$buscar}%"
                        )
                        ->orWhere(
                            'modelo',
                            'like',
                            "%{$buscar}%"
                        );
                }
            );
        }

        if ($request->filled('estado_operativo')) {
            $query->where(
                'estado_operativo',
                $request->estado_operativo
            );
        }

        if ($request->filled('municipio_id')) {
            $query->where(
                'municipio_id',
                $request->municipio_id
            );
        }

        $camiones = $query
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        $municipios =
            Municipio::orderBy('nombre')->get();

        return view(
            'admin.camiones.index',
            compact('camiones', 'municipios')
        );
    }

    public function create()
    {
        Gate::authorize('create', Camion::class);

        $municipios =
            Municipio::orderBy('nombre')->get();

        return view(
            'admin.camiones.create',
            compact('municipios')
        );
    }

    public function store(
        StoreCamionRequest $request
    ) {
        Gate::authorize('create', Camion::class);

        $validated = $request->validated();

        /*
         * El camión y sus credenciales se crean
         * dentro de una misma transacción.
         *
         * Si no podemos crear la cuenta de acceso,
         * tampoco se registra el vehículo.
         */
        DB::transaction(function () use ($validated): void {
            $camion = Camion::create([
                'matricula' => $validated['matricula'],
                'marca' => $validated['marca'],
                'modelo' => $validated['modelo'],
                'estado_operativo' =>
                    $validated['estado_operativo'],
                'municipio_id' =>
                    $validated['municipio_id']
                    ?? null,
            ]);

            /*
             * La matrícula es el nombre de usuario.
             * No se duplica en camion_user.
             *
             * Solamente almacenamos el hash.
             */
            $usuario = new CamionUser();

            $usuario->camion_id = $camion->id;

            $usuario->password = Hash::make(
                $validated['password']
            );

            $usuario->save();
        });

        return redirect()
            ->route('admin.camiones.index')
            ->with(
                'success',
                'Camión y credenciales creados correctamente.'
            );
    }

    public function show(
        Camion $camion
    ) {
        Gate::authorize('view', $camion);

        $camion->load([
            'municipio',
            'asignaciones.denuncia',
        ]);

        return view(
            'admin.camiones.show',
            compact('camion')
        );
    }

    public function edit(
        Camion $camion
    ) {
        Gate::authorize('update', $camion);

        $municipios =
            Municipio::orderBy('nombre')->get();

        return view(
            'admin.camiones.edit',
            compact('camion', 'municipios')
        );
    }

    public function update(
        UpdateCamionRequest $request,
        Camion $camion
    ) {
        Gate::authorize('update', $camion);

        $validated = $request->validated();

        /*
         * La actualización de datos administrativos
         * y de credenciales utiliza una transacción.
         */
        DB::transaction(function () use (
            $camion,
            $validated
        ): void {
            $camion->update([
                'matricula' => $validated['matricula'],
                'marca' => $validated['marca'],
                'modelo' => $validated['modelo'],
                'estado_operativo' =>
                    $validated['estado_operativo'],
                'municipio_id' =>
                    $validated['municipio_id']
                    ?? null,
            ]);

            /*
             * Si no se ingresó contraseña nueva,
             * conservamos la credencial existente.
             *
             * Para camiones históricos sin usuario,
             * la nueva contraseña permite crear
             * su cuenta en camion_user.
             */
            if (!empty($validated['password'])) {
                $usuario = CamionUser::query()
                    ->where('camion_id', $camion->id)
                    ->lockForUpdate()
                    ->first();

                if (!$usuario) {
                    $usuario = new CamionUser();
                    $usuario->camion_id = $camion->id;
                }

                $usuario->password = Hash::make(
                    $validated['password']
                );

                $usuario->save();
            }
        });

        return redirect()
            ->route('admin.camiones.index')
            ->with(
                'success',
                'Camión actualizado correctamente.'
            );
    }

    public function asignarMunicipio(
        Request $request,
        Camion $camion
    ) {
        Gate::authorize(
            'assignMunicipio',
            $camion
        );

        $validated = $request->validate([
            'municipio_id' => [
                'nullable',
                'integer',
                'exists:municipios,id',
            ],
        ]);

        $camion->municipio_id =
            $validated['municipio_id']
            ?? null;

        $camion->save();

        return back()->with(
            'success',
            'Municipio del camión actualizado correctamente.'
        );
    }

    public function destroy(
        Camion $camion
    ) {
        Gate::authorize('delete', $camion);

        /*
         * La cuenta en camion_user depende del
         * camión mediante la FK con cascada.
         *
         * No modificamos el comportamiento
         * administrativo existente de eliminación.
         */
        $camion->delete();

        return redirect()
            ->route('admin.camiones.index')
            ->with(
                'success',
                'Camión eliminado correctamente.'
            );
    }
}