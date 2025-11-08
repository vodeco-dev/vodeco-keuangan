<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\PassThroughPackage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassThroughPackageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_pass_through_package_routes(): void
    {
        $response = $this->post('/pass-through/packages', []);
        $response->assertRedirect('/login');
    }

    public function test_store_creates_new_package(): void
    {
        $user = User::factory()->create(['role' => Role::STAFF]);

        $response = $this->actingAs($user)->post('/pass-through/packages', [
            'name' => 'Test Package',
            'customer_type' => 'new',
            'daily_balance' => 1000000,
            'duration_days' => 30,
            'maintenance_fee' => 50000,
            'account_creation_fee' => 100000,
        ]);

        $response->assertRedirect('/debts');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('pass_through_packages', [
            'name' => 'Test Package',
            'customer_type' => 'new',
        ]);
    }

    public function test_store_requires_valid_data(): void
    {
        $user = User::factory()->create(['role' => Role::STAFF]);

        $response = $this->actingAs($user)->post('/pass-through/packages', []);

        $response->assertStatus(302); // Redirect back with errors
        // Check errors in the specific error bag
        $response->assertSessionHasErrorsIn('passThroughPackage', ['name', 'customer_type', 'daily_balance', 'duration_days', 'maintenance_fee']);
    }

    public function test_update_modifies_existing_package(): void
    {
        $user = User::factory()->create(['role' => Role::STAFF]);
        $package = PassThroughPackage::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->put("/pass-through/packages/{$package->uuid}", [
            'name' => 'Updated Package',
            'customer_type' => 'existing',
            'daily_balance' => 2000000,
            'duration_days' => 60,
            'maintenance_fee' => 75000,
        ]);

        $response->assertRedirect('/debts');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('pass_through_packages', [
            'id' => $package->id,
            'name' => 'Updated Package',
        ]);
    }

    public function test_destroy_deletes_package(): void
    {
        $user = User::factory()->create(['role' => Role::STAFF]);
        $package = PassThroughPackage::factory()->create();

        $response = $this->actingAs($user)->delete("/pass-through/packages/{$package->uuid}");

        $response->assertRedirect('/debts');
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('pass_through_packages', [
            'id' => $package->id,
        ]);
    }

    public function test_update_invoice_category_saves_setting(): void
    {
        $user = User::factory()->create(['role' => Role::STAFF]);
        $category = Category::factory()->create(['type' => 'pemasukan']);

        $response = $this->actingAs($user)->post('/pass-through/invoice-category', [
            'category_id' => $category->id,
        ]);

        $response->assertRedirect('/debts');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('settings', [
            'key' => 'pass_through_invoice_category_id',
            'value' => (string) $category->id,
        ]);
    }

    public function test_update_invoice_category_removes_setting_when_null(): void
    {
        $user = User::factory()->create(['role' => Role::STAFF]);
        Setting::create([
            'key' => 'pass_through_invoice_category_id',
            'value' => '123',
        ]);

        $response = $this->actingAs($user)->post('/pass-through/invoice-category', [
            'category_id' => null,
        ]);

        $response->assertRedirect('/debts');
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('settings', [
            'key' => 'pass_through_invoice_category_id',
        ]);
    }

    public function test_update_invoice_category_validates_category_exists(): void
    {
        $user = User::factory()->create(['role' => Role::STAFF]);

        $response = $this->actingAs($user)->post('/pass-through/invoice-category', [
            'category_id' => 99999,
        ]);

        $response->assertStatus(302); // Redirect back with errors
        $response->assertSessionHasErrorsIn('passThroughInvoiceCategory', ['category_id']);
    }
}

