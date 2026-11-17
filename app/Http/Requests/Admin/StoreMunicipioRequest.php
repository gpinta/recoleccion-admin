<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMunicipioRequest extends FormRequest
{
    public function authorize(): bool
    {
        /*
         * La autorización administrativa se realiza
         * explícitamente mediante Gate en el controlador.
         *
         * No usamos $this->user()->can() porque el usuario
         * autenticado pertenece a LdapRecord.
         */
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',

                Rule::unique(
                    'municipios',
                    'nombre'
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