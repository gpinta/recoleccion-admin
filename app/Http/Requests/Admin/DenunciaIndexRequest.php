<?php

namespace App\Http\Requests\Admin;

use App\Enums\DenunciaEstado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DenunciaIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => [
                'nullable',

                Rule::in(
                    DenunciaEstado::values()
                ),
            ],

            'buscar' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.in' =>
                'El estado seleccionado no es válido.',

            'buscar.integer' =>
                'El identificador de denuncia debe ser numérico.',

            'buscar.min' =>
                'El identificador de denuncia no es válido.',
        ];
    }
}