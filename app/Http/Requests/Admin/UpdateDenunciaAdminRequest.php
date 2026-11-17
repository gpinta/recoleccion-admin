<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDenunciaAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        /*
         * La autorización se realiza explícitamente
         * mediante DenunciaPolicy en el controlador.
         */
        return true;
    }

    public function rules(): array
    {
        return [
            'direccion' => [
                'required',
                'string',
                'max:255',
            ],

            'barrio_id' => [
                'required',
                'integer',
                'exists:barrios,id',
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'direccion.required' =>
                'La dirección es obligatoria.',

            'direccion.max' =>
                'La dirección no puede superar los 255 caracteres.',

            'barrio_id.required' =>
                'Debes seleccionar un barrio.',

            'barrio_id.exists' =>
                'El barrio seleccionado no existe.',

            'descripcion.max' =>
                'La descripción no puede superar los 5000 caracteres.',
        ];
    }
}
