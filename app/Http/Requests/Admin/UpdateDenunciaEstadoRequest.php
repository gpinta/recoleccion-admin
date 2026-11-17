<?php

namespace App\Http\Requests\Admin;

use App\Enums\DenunciaEstado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDenunciaEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => [
                'required',

                Rule::in(
                    DenunciaEstado::values()
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' =>
                'Debes seleccionar un estado.',

            'estado.in' =>
                'El estado seleccionado no es válido.',
        ];
    }
}