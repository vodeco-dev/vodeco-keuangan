<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionDeletionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_deletion_requests(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['type' => 'pengeluaran']);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $otherTransaction = Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
        ]);

        $ownRequest = TransactionDeletionRequest::create([
            'transaction_id' => $transaction->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'deletion_reason' => 'Duplikasi data',
        ]);

        $otherRequest = TransactionDeletionRequest::create([
            'transaction_id' => $otherTransaction->id,
            'requested_by' => $otherUser->id,
            'status' => 'pending',
            'deletion_reason' => 'Kesalahan input',
        ]);

        $response = $this->actingAs($user)->get(route('user-deletion-requests.index'));

        $response->assertOk();
        $response->assertViewIs('user_deletion_requests.index');
        $response->assertViewHas('requests', function ($paginator) use ($ownRequest, $otherRequest) {
            return $paginator->contains(fn ($item) => $item->id === $ownRequest->id)
                && ! $paginator->contains(fn ($item) => $item->id === $otherRequest->id);
        });
    }
}
