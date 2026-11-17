<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMunicipioRequest extends FormRequest
{
    public function authorize(): bool
    {
        /*
         * La autorización se realiza explícitamente
         * mediante MunicipioPolicy en el controlador.
         */
        return true;
    }

    public function rules(): array
    {
        $municipio = $this->route('municipio');

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',

                Rule::unique(
                    'municipios',
                    'nombre'
                )->ignore(
                    $municipio->id
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' =>
                'El nombre del municipio es obligatorio.',

            'nombre.unique' =>
                'Ya existe un municipio con ese nombre.',
        ];
    }
}
