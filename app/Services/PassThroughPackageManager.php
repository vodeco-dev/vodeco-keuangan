<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\PassThroughPackage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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

        return collect($decoded)
            ->filter(fn ($item) => is_array($item))
            ->map(fn ($item) => PassThroughPackage::fromArray($item))
            ->values();
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
}
