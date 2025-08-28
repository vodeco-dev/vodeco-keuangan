<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_transactions()
    {
        $response = $this->get('/transactions');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_transactions_page()
    {
        $user = User::factory()->create();
        Transaction::factory()->create();

        $response = $this->actingAs($user)->get('/transactions');
        $response->assertStatus(200);
    }

    public function test_store_requires_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/transactions', []);
        $response->assertSessionHasErrors(['category_id', 'amount']);
    }

    public function test_store_creates_transaction()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post('/transactions', [
            'category_id' => $category->id,
            'amount' => 1000,
            'description' => 'Test',
        ]);

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', [
            'category_id' => $category->id,
            'amount' => 1000,
            'description' => 'Test',
        ]);
    }

    public function test_update_transaction()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->put("/transactions/{$transaction->id}", [
            'category_id' => $category->id,
            'amount' => 500,
            'description' => 'Updated',
        ]);

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'category_id' => $category->id,
            'amount' => 500,
            'description' => 'Updated',
        ]);
    }

    public function test_delete_transaction()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create();

        $response = $this->actingAs($user)->delete("/transactions/{$transaction->id}");

        $response->assertRedirect('/transactions');
        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }
}
