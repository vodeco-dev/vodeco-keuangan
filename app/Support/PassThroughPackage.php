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
    public float $dailyBalance;
    public int $durationDays;
    public float $maintenanceFee;
    public float $accountCreationFee;

    public function __construct(array $attributes)
    {
        $this->id = (string) ($attributes['id'] ?? Str::uuid());
        $this->name = (string) ($attributes['name'] ?? '');
        $this->customerType = (string) ($attributes['customer_type'] ?? self::CUSTOMER_TYPE_NEW);
        $this->dailyBalance = (float) ($attributes['daily_balance'] ?? 0);
        $this->durationDays = (int) ($attributes['duration_days'] ?? 0);
        $this->maintenanceFee = (float) ($attributes['maintenance_fee'] ?? 0);
        $this->accountCreationFee = (float) ($attributes['account_creation_fee'] ?? 0);
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
            'daily_balance' => $this->dailyBalance,
            'duration_days' => $this->durationDays,
            'maintenance_fee' => $this->maintenanceFee,
            'account_creation_fee' => $this->accountCreationFee,
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
        return max($this->dailyBalance * $this->durationDays, 0);
    }

    public function totalAdBudget(): float
    {
        return $this->remainingPassThroughAmount();
    }
}
