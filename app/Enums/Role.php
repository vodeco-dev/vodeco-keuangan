<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case ACCOUNTANT = 'accountant';
    case STAFF = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::ACCOUNTANT => 'Accountant',
            self::STAFF => 'Staff',
        };
    }
}
