<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AsignarDenunciaManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'camion_id' => [
                'required',
                'integer',
                'exists:camiones,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'camion_id.required' =>
                'Debes seleccionar un camión.',

            'camion_id.integer' =>
                'El camión seleccionado no es válido.',

            'camion_id.exists' =>
                'El camión seleccionado no existe.',
        ];
    }
}