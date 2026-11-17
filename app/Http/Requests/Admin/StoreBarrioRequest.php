<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBarrioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
            ],

            'municipio_id' => [
                'required',
                'integer',
                'exists:municipios,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' =>
                'El nombre del barrio es obligatorio.',

            'municipio_id.required' =>
                'Debes seleccionar un municipio.',

            'municipio_id.exists' =>
                'El municipio seleccionado no existe.',
        ];
    }
}