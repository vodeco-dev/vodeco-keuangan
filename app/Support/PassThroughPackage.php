<?php

namespace App\Support;

use App\Models\PassThroughPackage as PassThroughPackageModel;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class PassThroughPackage implements Arrayable
{
    public const CUSTOMER_TYPE_NEW = 'new';
    public const CUSTOMER_TYPE_EXISTING = 'existing';

    public ?int $modelId;
    public ?string $uuid;
    public ?string $id;
    public string $name;
    public string $customerType;
    public float $dailyBalance;
    public int $durationDays;
    public float $maintenanceFee;
    public float $accountCreationFee;
    public bool $isActive;
    public ?Carbon $createdAt;
    public ?Carbon $updatedAt;

    public function __construct(array $attributes = [])
    {
        $this->modelId = isset($attributes['model_id']) ? (int) $attributes['model_id'] : ($attributes['modelId'] ?? null);
        $uuid = Arr::get($attributes, 'uuid', Arr::get($attributes, 'id'));
        $this->uuid = $uuid !== null ? (string) $uuid : null;
        $this->id = $this->uuid;
        $this->name = (string) Arr::get($attributes, 'name', '');
        $this->customerType = (string) Arr::get($attributes, 'customer_type', self::CUSTOMER_TYPE_NEW);
        $this->dailyBalance = (float) Arr::get($attributes, 'daily_balance', 0);
        $this->durationDays = (int) Arr::get($attributes, 'duration_days', 0);
        $this->maintenanceFee = (float) Arr::get($attributes, 'maintenance_fee', 0);
        $this->accountCreationFee = (float) Arr::get($attributes, 'account_creation_fee', 0);
        $this->isActive = (bool) Arr::get($attributes, 'is_active', true);

        $createdAt = Arr::get($attributes, 'created_at');
        $this->createdAt = $createdAt ? Carbon::parse($createdAt) : null;
        $updatedAt = Arr::get($attributes, 'updated_at');
        $this->updatedAt = $updatedAt ? Carbon::parse($updatedAt) : null;
    }

    public static function fromModel(PassThroughPackageModel $model): self
    {
        return new self([
            'model_id' => $model->getKey(),
            'uuid' => $model->uuid,
            'name' => $model->name,
            'customer_type' => $model->customer_type,
            'daily_balance' => $model->daily_balance,
            'duration_days' => $model->duration_days,
            'maintenance_fee' => $model->maintenance_fee,
            'account_creation_fee' => $model->account_creation_fee,
            'is_active' => $model->is_active,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at,
        ]);
    }

    public function customerLabel(): string
    {
        return $this->customerType === self::CUSTOMER_TYPE_NEW
            ? 'Pelanggan Baru'
            : 'Pelanggan Lama';
    }

    public function isForNewCustomer(): bool
    {
        return $this->customerType === self::CUSTOMER_TYPE_NEW;
    }

    public function totalAdBudget(): float
    {
        return $this->dailyBalance * $this->durationDays;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'model_id' => $this->modelId,
            'name' => $this->name,
            'customer_type' => $this->customerType,
            'customer_label' => $this->customerLabel(),
            'daily_balance' => $this->dailyBalance,
            'duration_days' => $this->durationDays,
            'maintenance_fee' => $this->maintenanceFee,
            'account_creation_fee' => $this->accountCreationFee,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->toIso8601String(),
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
