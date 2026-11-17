<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBarrioRequest extends FormRequest
{
    public function authorize(): bool
    {
        /*
         * La autorización se realiza explícitamente
         * mediante BarrioPolicy en el controlador.
         */
        return true;
    }

    public function rules(): array
    {
        $barrio = $this->route('barrio');

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',

                Rule::unique(
                    'barrios',
                    'nombre'
                )
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'municipio_id',
                                $barrio->municipio_id
                            )
                    )
                    ->ignore($barrio->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' =>
                'El nombre del barrio es obligatorio.',

            'nombre.unique' =>
                'Ya existe un barrio con ese nombre en este municipio.',
        ];
    }
}
