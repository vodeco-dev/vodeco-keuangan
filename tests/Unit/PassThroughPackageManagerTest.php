<?php

namespace Tests\Unit;

use App\Models\PassThroughPackage;
use App\Services\PassThroughPackageManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassThroughPackageManagerTest extends TestCase
{
    use RefreshDatabase;

    private PassThroughPackageManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new PassThroughPackageManager();
    }

    public function test_all_returns_only_active_packages(): void
    {
        PassThroughPackage::factory()->create(['is_active' => true, 'name' => 'Package A']);
        PassThroughPackage::factory()->create(['is_active' => true, 'name' => 'Package B']);
        PassThroughPackage::factory()->create(['is_active' => false, 'name' => 'Package C']);

        $packages = $this->manager->all();

        $this->assertCount(2, $packages);
        $this->assertEquals('Package A', $packages->first()->name);
    }

    public function test_find_returns_package_by_uuid(): void
    {
        $package = PassThroughPackage::factory()->create(['name' => 'Test Package']);

        $found = $this->manager->find($package->uuid);

        $this->assertNotNull($found);
        $this->assertEquals('Test Package', $found->name);
    }

    public function test_find_returns_null_when_package_not_found(): void
    {
        $found = $this->manager->find('non-existent-uuid');

        $this->assertNull($found);
    }

    public function test_store_creates_new_package(): void
    {
        $data = [
            'name' => 'New Package',
            'uuid' => 'test-uuid-123',
            'customer_type' => 'new',
            'daily_balance' => 1000000,
            'duration_days' => 30,
            'maintenance_fee' => 50000,
            'account_creation_fee' => 100000,
            'is_active' => true,
        ];

        $package = $this->manager->store($data);

        $this->assertDatabaseHas('pass_through_packages', [
            'name' => 'New Package',
            'uuid' => 'test-uuid-123',
        ]);
        $this->assertInstanceOf(PassThroughPackage::class, $package);
    }

    public function test_update_modifies_existing_package(): void
    {
        $package = PassThroughPackage::factory()->create(['name' => 'Old Name']);

        $result = $this->manager->update($package, ['name' => 'New Name']);

        $this->assertTrue($result);
        $this->assertDatabaseHas('pass_through_packages', [
            'id' => $package->id,
            'name' => 'New Name',
        ]);
    }

    public function test_delete_removes_package_by_uuid(): void
    {
        $package = PassThroughPackage::factory()->create();

        $result = $this->manager->delete($package->uuid);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('pass_through_packages', [
            'id' => $package->id,
        ]);
    }

    public function test_delete_returns_false_when_package_not_found(): void
    {
        $result = $this->manager->delete('non-existent-uuid');

        $this->assertFalse($result);
    }
}

