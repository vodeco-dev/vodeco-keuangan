<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case ACCOUNTANT = 'accountant';
    case STAFF = 'staff';
    case CUSTOMER_SERVICE = 'customer_service';
    case SETTLEMENT_ADMIN = 'settlement_admin';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::ACCOUNTANT => 'Accountant',
            self::STAFF => 'Staff',
            self::CUSTOMER_SERVICE => 'Customer Service',
            self::SETTLEMENT_ADMIN => 'Admin Pelunasan',
        };
    }
}
