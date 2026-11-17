<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        if (!is_string($value)) {
            $fail('La contraseña no es válida.');
            return;
        }

        if (strlen($value) < 8) {
            $fail('La contraseña debe tener al menos 8 caracteres.');
            return;
        }

        if (!preg_match('/[a-z]/', $value)) {
            $fail('La contraseña debe contener al menos una letra minúscula.');
            return;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $fail('La contraseña debe contener al menos una letra mayúscula.');
            return;
        }

        if (!preg_match('/\d/', $value)) {
            $fail('La contraseña debe contener al menos un número.');
            return;
        }

        if (!preg_match('/[@.\-_$!]/', $value)) {
            $fail('La contraseña debe contener al menos un carácter especial (@ . - _ $ !).');
            return;
        }

        if (!preg_match('/^[A-Za-z\d@.\-_$!]+$/', $value)) {
            $fail('La contraseña contiene caracteres no permitidos.');
        }
    }
}