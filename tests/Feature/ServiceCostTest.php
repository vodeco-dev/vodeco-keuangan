<?php

namespace Tests\Feature;

use App\Models\ServiceCost;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_service_costs()
    {
        $response = $this->get('/service_costs');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_service_costs_page()
    {
        $user = User::factory()->create();
        ServiceCost::factory()->count(2)->create();

        $response = $this->actingAs($user)->get('/service_costs');
        $response->assertStatus(200);
    }

    public function test_store_requires_name()
    {
        $user = User::factory()->create(['role' => \App\Enums\Role::ADMIN]);

        $response = $this->actingAs($user)->post('/service_costs', []);
        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_creates_service_cost()
    {
        $user = User::factory()->create(['role' => \App\Enums\Role::ADMIN]);

        $response = $this->actingAs($user)->post('/service_costs', [
            'name' => 'Consulting',
        ]);

        $response->assertRedirect('/service_costs');
        $this->assertDatabaseHas('service_costs', [
            'name' => 'Consulting',
            'slug' => 'consulting',
        ]);
    }

    public function test_update_service_cost()
    {
        $user = User::factory()->create(['role' => \App\Enums\Role::ADMIN]);
        $serviceCost = ServiceCost::factory()->create(['name' => 'Old', 'slug' => 'old']);

        $response = $this->actingAs($user)->put("/service_costs/{$serviceCost->id}", [
            'name' => 'Updated',
        ]);

        $response->assertRedirect('/service_costs');
        $this->assertDatabaseHas('service_costs', [
            'id' => $serviceCost->id,
            'name' => 'Updated',
            'slug' => 'updated',
        ]);
    }

    public function test_delete_service_cost()
    {
        $user = User::factory()->create(['role' => \App\Enums\Role::ADMIN]);
        $serviceCost = ServiceCost::factory()->create();

        $response = $this->actingAs($user)->delete("/service_costs/{$serviceCost->id}");

        $response->assertRedirect('/service_costs');
        $this->assertDatabaseMissing('service_costs', ['id' => $serviceCost->id]);
    }

    public function test_cannot_delete_service_cost_when_used_by_transaction()
    {
        $user = User::factory()->create(['role' => \App\Enums\Role::ADMIN]);
        $serviceCost = ServiceCost::factory()->create();
        Transaction::factory()->create([
            'service_cost_id' => $serviceCost->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete("/service_costs/{$serviceCost->id}");

        $response->assertRedirect('/service_costs');
        $this->assertDatabaseHas('service_costs', ['id' => $serviceCost->id]);
    }
}
