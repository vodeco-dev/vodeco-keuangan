<?php

namespace App\Services;

use App\Models\PassThroughPackage;
use Illuminate\Support\Collection;

class PassThroughPackageManager
{
    /**
     * Retrieve all active packages.
     */
    public function all(): Collection
    {
        return PassThroughPackage::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Find a package by its UUID.
     */
    public function find(string $uuid): ?PassThroughPackage
    {
        return PassThroughPackage::where('uuid', $uuid)->first();
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
        $package = $this->find($uuid);

        if ($package) {
            return $package->delete();
        }

        return false;
    }
}
