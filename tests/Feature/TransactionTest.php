<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionDeletionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_transactions(): void
    {
        $response = $this->get('/transactions');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_all_transactions(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $transaction1 = Transaction::factory()->create(['user_id' => $user1->id, 'description' => 'Transaction 1']);
        $transaction2 = Transaction::factory()->create(['user_id' => $user2->id, 'description' => 'Transaction 2']);

        $response = $this->actingAs($user1)->get('/transactions');

        $response->assertStatus(200);
        $response->assertSeeText('Transaction 1');
        $response->assertSeeText('Transaction 2');
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

    public function test_non_admin_deletion_creates_request(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete('/transactions/' . $transaction->id, [
            'reason' => 'Butuh koreksi data',
        ]);
        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transaction_deletion_requests', [
            'transaction_id' => $transaction->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'deletion_reason' => 'Butuh koreksi data',
        ]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_admin_can_delete_transaction(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $transaction = Transaction::factory()->create(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->delete('/transactions/' . $transaction->id);
        $response->assertRedirect('/transactions');
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_admin_can_view_transaction_proof_of_another_user(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $owner = User::factory()->create();

        $transaction = Transaction::factory()->create([
            'user_id' => $owner->id,
            'proof_disk' => 'local',
            'proof_directory' => 'proofs',
            'proof_path' => 'receipt.pdf',
            'proof_token' => Str::random(40),
        ]);

        Storage::disk('local')->put('proofs/receipt.pdf', 'dummy content');

        $response = $this->actingAs($admin)->get(route('transactions.proof.show', ['transaction' => $transaction->proof_token]));

        $response->assertOk();
    }

    public function test_accountant_can_view_transaction_proof_of_another_user(): void
    {
        Storage::fake('local');

        $accountant = User::factory()->create(['role' => Role::ACCOUNTANT]);
        $owner = User::factory()->create();

        $transaction = Transaction::factory()->create([
            'user_id' => $owner->id,
            'proof_disk' => 'local',
            'proof_directory' => 'proofs',
            'proof_path' => 'receipt.pdf',
            'proof_token' => Str::random(40),
        ]);

        Storage::disk('local')->put('proofs/receipt.pdf', 'dummy content');

        $response = $this->actingAs($accountant)->get(route('transactions.proof.show', ['transaction' => $transaction->proof_token]));

        $response->assertOk();
    }

    public function test_staff_cannot_view_transaction_proof_of_another_user(): void
    {
        Storage::fake('local');

        $staff = User::factory()->create(['role' => Role::STAFF]);
        $owner = User::factory()->create();

        $transaction = Transaction::factory()->create([
            'user_id' => $owner->id,
            'proof_disk' => 'local',
            'proof_directory' => 'proofs',
            'proof_path' => 'receipt.pdf',
            'proof_token' => Str::random(40),
        ]);

        Storage::disk('local')->put('proofs/receipt.pdf', 'dummy content');

        $response = $this->actingAs($staff)->get(route('transactions.proof.show', ['transaction' => $transaction->proof_token]));

        $response->assertStatus(403);
    }

    public function test_admin_can_approve_deletion_request(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);
        $request = TransactionDeletionRequest::create([
            'transaction_id' => $transaction->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'deletion_reason' => 'Butuh koreksi data',
        ]);

        $response = $this->actingAs($admin)->post('/admin/deletion-requests/' . $request->id . '/approve');
        $response->assertRedirect('/admin/deletion-requests');
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
        $this->assertDatabaseHas('transaction_deletion_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
    }
}

