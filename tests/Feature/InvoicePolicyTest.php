<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_any_invoice()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = Invoice::factory()->create();

        $this->actingAs($admin);

        $response = $this->get(route('invoices.show', $invoice));

        $response->assertStatus(200);
    }

    public function test_user_can_view_own_invoice()
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->get(route('invoices.show', $invoice));

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_other_users_invoice()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user2->id]);

        $this->actingAs($user1);

        $response = $this->get(route('invoices.show', $invoice));

        $response->assertStatus(403);
    }

    public function test_admin_can_update_any_invoice()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = Invoice::factory()->create();

        $this->actingAs($admin);

        $category = Category::factory()->create(['type' => 'pemasukan']);

        $response = $this->put(route('invoices.update', $invoice), [
            'client_name' => 'Jane Doe',
            'client_whatsapp' => '08123456789',
            'client_address' => '123 Main St',
            'due_date' => '2025-12-31',
            'items' => [
                ['description' => 'New Item', 'quantity' => 1, 'price' => 100, 'category_id' => $category->id],
            ],
        ]);

        $response->assertStatus(302);
    }

    public function test_user_can_update_own_invoice()
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $category = Category::factory()->create(['type' => 'pemasukan']);

        $response = $this->put(route('invoices.update', $invoice), [
            'client_name' => 'Jane Doe',
            'client_whatsapp' => '08123456789',
            'client_address' => '123 Main St',
            'due_date' => '2025-12-31',
            'items' => [
                ['description' => 'New Item', 'quantity' => 1, 'price' => 100, 'category_id' => $category->id],
            ],
        ]);

        $response->assertStatus(302);
    }

    public function test_user_cannot_update_other_users_invoice()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user2->id]);

        $this->actingAs($user1);

        $category = Category::factory()->create(['type' => 'pemasukan']);

        $response = $this->put(route('invoices.update', $invoice), [
            'client_name' => 'Jane Doe',
            'client_whatsapp' => '08123456789',
            'client_address' => '123 Main St',
            'due_date' => '2025-12-31',
            'items' => [
                ['description' => 'New Item', 'quantity' => 1, 'price' => 100, 'category_id' => $category->id],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_any_invoice()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = Invoice::factory()->create();

        $this->actingAs($admin);

        $response = $this->delete(route('invoices.destroy', $invoice));

        $response->assertStatus(302);
    }

    public function test_user_can_delete_own_invoice()
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->delete(route('invoices.destroy', $invoice));

        $response->assertStatus(302);
    }

    public function test_user_cannot_delete_other_users_invoice()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user2->id]);

        $this->actingAs($user1);

        $response = $this->delete(route('invoices.destroy', $invoice));

        $response->assertStatus(403);
    }
}
