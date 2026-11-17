<?php

namespace App\Http\Requests\Admin;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCamionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'matricula' => [
                'required',
                'string',
                'max:20',

                Rule::unique(
                    'camiones',
                    'matricula'
                ),
            ],

            'marca' => [
                'required',
                'string',
                'max:100',
            ],

            'modelo' => [
                'required',
                'string',
                'max:100',
            ],

            'estado_operativo' => [
                'required',

                Rule::in([
                    'activo',
                    'inactivo',
                ]),
            ],

            'municipio_id' => [
                'nullable',
                'integer',
                'exists:municipios,id',
            ],

            /*
             * Credenciales iniciales del camión.
             *
             * La confirmación se envía mediante
             * password_confirmation y Laravel
             * comprueba que ambas coincidan.
             */
            'password' => [
                'required',
                'string',
                'confirmed',
                'max:255',
                new StrongPassword(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'matricula.required' =>
                'La matrícula es obligatoria.',

            'matricula.unique' =>
                'Ya existe un camión con esa matrícula.',

            'marca.required' =>
                'La marca es obligatoria.',

            'modelo.required' =>
                'El modelo es obligatorio.',

            'estado_operativo.required' =>
                'Debes seleccionar el estado operativo.',

            'estado_operativo.in' =>
                'El estado operativo seleccionado no es válido.',

            'municipio_id.exists' =>
                'El municipio seleccionado no existe.',

            'password.required' =>
                'La contraseña del camión es obligatoria.',

            'password.confirmed' =>
                'La confirmación de contraseña no coincide.',

            'password.max' =>
                'La contraseña es demasiado larga.',
        ];
    }
}