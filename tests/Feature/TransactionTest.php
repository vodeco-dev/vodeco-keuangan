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

    public function test_guest_cannot_access_transactions(): void
    {
        $response = $this->get('/transactions');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_transactions_page(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/transactions');
        $response->assertStatus(200);
    }

    public function test_store_requires_fields(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/transactions', []);
        $response->assertSessionHasErrors(['date', 'category_id', 'amount']);
    }

    public function test_store_creates_transaction(): void
    {
        $user = User::factory()->create();
        // PERBAIKAN: Menghapus 'user_id' saat membuat kategori
        $category = Category::factory()->create(['type' => 'pemasukan']);
        $response = $this->actingAs($user)->post('/transactions', [
            'date' => now()->toDateString(),
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

    public function test_update_transaction(): void
    {
        $user = User::factory()->create();
        // PERBAIKAN: Menghapus 'user_id' saat membuat kategori
        $category = Category::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put('/transactions/' . $transaction->id, [
            'date' => now()->addDay()->toDateString(),
            'category_id' => $category->id,
            'amount' => 500,
            'description' => 'Updated',
        ]);

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 500,
            'description' => 'Updated',
        ]);
    }

    public function test_delete_transaction(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete('/transactions/' . $transaction->id);
        $response->assertRedirect('/transactions');
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }
}

