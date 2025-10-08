<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class PassThroughPackage implements Arrayable
{
    public const CUSTOMER_TYPE_NEW = 'new';
    public const CUSTOMER_TYPE_EXISTING = 'existing';

    public string $id;
    public string $name;
    public string $customerType;
    public float $packagePrice;
    public float $dailyDeduction;
    public float $maintenanceFee;
    public float $accountCreationFee;
    public float $renewalFee;

    public function __construct(array $attributes)
    {
        $this->id = (string) ($attributes['id'] ?? Str::uuid());
        $this->name = (string) ($attributes['name'] ?? '');
        $this->customerType = (string) ($attributes['customer_type'] ?? self::CUSTOMER_TYPE_NEW);
        $this->packagePrice = (float) ($attributes['package_price'] ?? 0);
        $this->dailyDeduction = (float) ($attributes['daily_deduction'] ?? 0);
        $this->maintenanceFee = (float) ($attributes['maintenance_fee'] ?? 0);
        $this->accountCreationFee = (float) ($attributes['account_creation_fee'] ?? 0);
        $this->renewalFee = (float) ($attributes['renewal_fee'] ?? 0);
    }

    public static function fromArray(array $attributes): self
    {
        return new self($attributes);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'customer_type' => $this->customerType,
            'package_price' => $this->packagePrice,
            'daily_deduction' => $this->dailyDeduction,
            'maintenance_fee' => $this->maintenanceFee,
            'account_creation_fee' => $this->accountCreationFee,
            'renewal_fee' => $this->renewalFee,
        ];
    }

    public function customerLabel(): string
    {
        return $this->customerType === self::CUSTOMER_TYPE_NEW
            ? 'Pelanggan Baru'
            : 'Pelanggan Lama';
    }

    public function remainingPassThroughAmount(): float
    {
        $deductions = $this->maintenanceFee + $this->renewalFee;

        if ($this->customerType === self::CUSTOMER_TYPE_NEW) {
            $deductions += $this->accountCreationFee;
        }

        return max($this->packagePrice - $deductions, 0);
    }
}
