<?php

namespace App\Enums;

enum AdminRole: string
{
    case READER = 'reader';
    case OPERATOR = 'operator';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::READER => 'Reader',
            self::OPERATOR => 'Operator',
            self::ADMIN => 'Admin',
        };
    }

    public function puedeOperar(): bool
    {
        return in_array(
            $this,
            [
                self::OPERATOR,
                self::ADMIN,
            ],
            true
        );
    }

    public function esAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
