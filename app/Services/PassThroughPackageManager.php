<?php

namespace App\Services;

use App\Models\PassThroughPackage;
use App\Support\PassThroughPackage as PassThroughPackageData;
use Illuminate\Support\Collection;

class PassThroughPackageManager
{
    /**
     * Retrieve all active packages.
     */
    public function all(): Collection
    {
        return PassThroughPackage::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (PassThroughPackage $package) => PassThroughPackageData::fromModel($package));
    }

    /**
     * Find a package by its UUID.
     */
    public function find(string $uuid): ?PassThroughPackageData
    {
        $package = PassThroughPackage::where('uuid', $uuid)->first();

        return $package ? PassThroughPackageData::fromModel($package) : null;
    }

    /**
     * Create a new package.
     */
    public function store(array $data): PassThroughPackage
    {
        return PassThroughPackage::create($data);
    }

    /**
     * Update an existing package.
     */
    public function update(PassThroughPackage $package, array $data): bool
    {
        return $package->update($data);
    }

    /**
     * Delete a package by its UUID.
     */
    public function delete(string $uuid): bool
    {
        $package = PassThroughPackage::where('uuid', $uuid)->first();

        if ($package) {
            return $package->delete();
        }

        return false;
    }
}
