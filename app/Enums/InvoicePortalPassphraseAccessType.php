<?php

namespace App\Enums;

enum InvoicePortalPassphraseAccessType: string
{
    case CUSTOMER_SERVICE = 'customer_service';
    case ADMIN_PELUNASAN = 'admin_pelunasan';

    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER_SERVICE => 'Customer Service',
            self::ADMIN_PELUNASAN => 'Admin Pelunasan',
        };
    }

    /**
     * @return array<int, string>
     */
    public function allowedTransactionTypes(): array
    {
        return match ($this) {
            self::CUSTOMER_SERVICE => ['down_payment', 'full_payment'],
            self::ADMIN_PELUNASAN => ['settlement'],
        };
    }
}
