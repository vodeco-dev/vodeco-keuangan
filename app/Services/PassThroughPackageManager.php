<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\PassThroughPackage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PassThroughPackageManager
{
    public const SETTING_KEY = 'pass_through_packages';

    public function all(): Collection
    {
        $raw = Setting::get(self::SETTING_KEY);

        if (! is_string($raw) || $raw === '') {
            return collect();
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return collect();
        }

        $wasUpgraded = false;

        $packages = collect($decoded)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) use (&$wasUpgraded) {
                [$normalized, $upgraded] = $this->normalizePackagePayload($item);

                if ($upgraded) {
                    $wasUpgraded = true;
                }

                return PassThroughPackage::fromArray($normalized);
            })
            ->values();

        if ($wasUpgraded && $packages->isNotEmpty()) {
            $this->persist($packages);
        }

        return $packages;
    }

    public function find(string $id): ?PassThroughPackage
    {
        return $this->all()->firstWhere('id', $id);
    }

    public function save(PassThroughPackage $package): void
    {
        $packages = $this->all()
            ->keyBy('id');

        $packages[$package->id] = $package;

        $this->persist($packages->values());
    }

    public function delete(string $id): void
    {
        $packages = $this->all()
            ->reject(fn (PassThroughPackage $package) => $package->id === $id)
            ->values();

        $this->persist($packages);
    }

    protected function persist(Collection $packages): void
    {
        $payload = $packages
            ->map(fn (PassThroughPackage $package) => $package->toArray())
            ->values()
            ->all();

        Setting::updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => json_encode($payload)]
        );

        Cache::forget('setting:' . self::SETTING_KEY);
    }

    protected function normalizePackagePayload(array $attributes): array
    {
        $hasLegacyFields = array_key_exists('package_price', $attributes)
            || array_key_exists('daily_deduction', $attributes)
            || array_key_exists('renewal_fee', $attributes);

        if (! $hasLegacyFields) {
            return [$this->ensureDefaults($attributes), false];
        }

        $dailyBalance = (float) ($attributes['daily_balance'] ?? $attributes['daily_deduction'] ?? 0);
        $maintenanceFee = (float) ($attributes['maintenance_fee'] ?? 0);
        $accountCreationFee = (float) ($attributes['account_creation_fee'] ?? 0);
        $renewalFee = (float) ($attributes['renewal_fee'] ?? 0);
        $packagePrice = (float) ($attributes['package_price'] ?? 0);

        $adBudget = $packagePrice - $maintenanceFee - $accountCreationFee - $renewalFee;

        if ($adBudget < 0) {
            $adBudget = 0;
        }

        $durationDays = 0;

        if ($dailyBalance > 0) {
            $durationDays = (int) round($adBudget / $dailyBalance);
        }

        $normalized = [
            'id' => $attributes['id'] ?? null,
            'name' => $attributes['name'] ?? '',
            'customer_type' => $attributes['customer_type'] ?? PassThroughPackage::CUSTOMER_TYPE_NEW,
            'daily_balance' => $dailyBalance,
            'duration_days' => $durationDays,
            'maintenance_fee' => $maintenanceFee + $renewalFee,
            'account_creation_fee' => $accountCreationFee,
        ];

        return [$this->ensureDefaults($normalized), true];
    }

    protected function ensureDefaults(array $attributes): array
    {
        $attributes['id'] = (string) ($attributes['id'] ?? Str::uuid());
        $attributes['name'] = (string) ($attributes['name'] ?? '');
        $attributes['customer_type'] = (string) ($attributes['customer_type'] ?? PassThroughPackage::CUSTOMER_TYPE_NEW);
        $attributes['daily_balance'] = (float) ($attributes['daily_balance'] ?? 0);
        $attributes['duration_days'] = (int) ($attributes['duration_days'] ?? 0);
        $attributes['maintenance_fee'] = (float) ($attributes['maintenance_fee'] ?? 0);
        $attributes['account_creation_fee'] = (float) ($attributes['account_creation_fee'] ?? 0);

        return $attributes;
    }
}
