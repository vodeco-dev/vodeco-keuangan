<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Project;
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
        // Memastikan client_id dan project_id juga divalidasi
        $response->assertSessionHasErrors(['date', 'category_id', 'client_id', 'project_id', 'amount']);
    }

    public function test_store_creates_transaction(): void
    {
        $user = User::factory()->create();
        // PERBAIKAN: Menghapus 'user_id' saat membuat kategori
        $category = Category::factory()->create(['type' => 'pemasukan']);
        // Membuat data Client dan Project yang dibutuhkan
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($user)->post('/transactions', [
            'date' => now()->toDateString(),
            'category_id' => $category->id,
            'client_id' => $client->id, // Menyertakan client_id
            'project_id' => $project->id, // Menyertakan project_id
            'amount' => 1000,
            'description' => 'Test',
        ]);

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', [
            'category_id' => $category->id,
            'project_id' => $project->id, // Memeriksa project_id
            'amount' => 1000,
            'description' => 'Test',
        ]);
    }

    public function test_update_transaction(): void
    {
        $user = User::factory()->create();
        // PERBAIKAN: Menghapus 'user_id' saat membuat kategori
        $category = Category::factory()->create();
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        // Membuat data baru untuk pembaruan
        $newClient = Client::factory()->create();
        $newProject = Project::factory()->create(['client_id' => $newClient->id]);

        $response = $this->actingAs($user)->put('/transactions/' . $transaction->id, [
            'date' => now()->addDay()->toDateString(),
            'category_id' => $category->id,
            'client_id' => $newClient->id, // Menggunakan data baru
            'project_id' => $newProject->id, // Menggunakan data baru
            'amount' => 500,
            'description' => 'Updated',
        ]);

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'project_id' => $newProject->id, // Memeriksa data yang sudah diperbarui
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

