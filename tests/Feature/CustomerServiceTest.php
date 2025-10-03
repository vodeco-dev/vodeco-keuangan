<?php

namespace Tests\Feature;

use App\Models\CustomerService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_lists_customer_services(): void
    {
        $user = User::factory()->create();
        $first = CustomerService::create([
            'name' => 'Rina',
            'email' => 'rina@example.com',
            'phone' => '081234567890',
        ]);
        CustomerService::create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '081111111111',
        ]);

        $response = $this->actingAs($user)->get(route('customer-services.create'));

        $response->assertOk();
        $response->assertViewIs('customer-services.create');
        $response->assertViewHas('customerServices', function ($paginator) use ($first) {
            return $paginator->contains($first);
        });
    }

    public function test_store_validates_and_creates_customer_service(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customer-services.store'), [
            'name' => 'Tim Dukungan',
            'email' => 'support@example.com',
            'phone' => '081234567890',
        ]);

        $response->assertRedirect(route('customer-services.create'));
        $response->assertSessionHas('status', 'Customer service berhasil ditambahkan.');

        $this->assertDatabaseHas('customer_services', [
            'name' => 'Tim Dukungan',
            'email' => 'support@example.com',
            'phone' => '081234567890',
        ]);
    }

    public function test_store_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('customer-services.store'), [
            'name' => '',
            'email' => 'invalid@example.com',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('customer_services', 0);
    }
}
