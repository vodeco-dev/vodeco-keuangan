<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionDeletionRequest;
use App\Models\User;
use App\Notifications\TransactionApproved;
use App\Notifications\TransactionRejected;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminDeletionApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pending_deletion_requests(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $requester = User::factory()->create();
        $otherRequester = User::factory()->create();
        $category = Category::factory()->create(['type' => 'pengeluaran']);

        $transaction = Transaction::factory()->create([
            'user_id' => $requester->id,
            'category_id' => $category->id,
        ]);

        $otherTransaction = Transaction::factory()->create([
            'user_id' => $otherRequester->id,
            'category_id' => $category->id,
        ]);

        $pending = TransactionDeletionRequest::create([
            'transaction_id' => $transaction->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        TransactionDeletionRequest::create([
            'transaction_id' => $otherTransaction->id,
            'requested_by' => $otherRequester->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.deletion-requests.index'));

        $response->assertOk();
        $response->assertViewIs('admin.deletion_requests.index');
        $response->assertViewHas('requests', function ($collection) use ($pending) {
            return $collection->contains(fn ($item) => $item->id === $pending->id)
                && $collection->every(fn ($item) => $item->status === 'pending');
        });
    }

    public function test_admin_can_approve_deletion_request(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $requester = User::factory()->create();
        $category = Category::factory()->create(['type' => 'pengeluaran']);

        $transaction = Transaction::factory()->create([
            'user_id' => $requester->id,
            'category_id' => $category->id,
            'amount' => 75000,
        ]);

        $deletionRequest = TransactionDeletionRequest::create([
            'transaction_id' => $transaction->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        Cache::forget('setting:notify_transaction_approved');
        Setting::query()->updateOrCreate([
            'key' => 'notify_transaction_approved',
        ], [
            'value' => true,
        ]);

        $serviceMock = \Mockery::mock(TransactionService::class);
        $serviceMock->shouldReceive('clearSummaryCacheForUser')->once();
        $this->app->instance(TransactionService::class, $serviceMock);

        $response = $this->actingAs($admin)->post(route('admin.deletion-requests.approve', $deletionRequest));

        $response->assertRedirect(route('admin.deletion-requests.index'));
        $response->assertSessionHas('success', 'Permintaan penghapusan disetujui.');

        $this->assertNull(Transaction::find($transaction->id));

        $deletionRequest->refresh();
        $this->assertSame('approved', $deletionRequest->status);
        $this->assertSame($admin->id, $deletionRequest->approved_by);
        $this->assertNotNull($deletionRequest->approved_at);

        Notification::assertSentTo($requester, TransactionApproved::class);
    }

    public function test_admin_can_reject_deletion_request_with_reason(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $requester = User::factory()->create();
        $category = Category::factory()->create(['type' => 'pengeluaran']);

        $transaction = Transaction::factory()->create([
            'user_id' => $requester->id,
            'category_id' => $category->id,
        ]);

        $deletionRequest = TransactionDeletionRequest::create([
            'transaction_id' => $transaction->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        $serviceMock = \Mockery::mock(TransactionService::class);
        $serviceMock->shouldReceive('clearSummaryCacheForUser')->never();
        $this->app->instance(TransactionService::class, $serviceMock);

        $response = $this->actingAs($admin)->post(route('admin.deletion-requests.reject', $deletionRequest), [
            'reason' => 'Data masih diperlukan',
        ]);

        $response->assertRedirect(route('admin.deletion-requests.index'));
        $response->assertSessionHas('success', 'Permintaan penghapusan ditolak.');

        $deletionRequest->refresh();
        $this->assertSame('rejected', $deletionRequest->status);
        $this->assertSame('Data masih diperlukan', $deletionRequest->reason);
        $this->assertSame($admin->id, $deletionRequest->approved_by);
        $this->assertNotNull($deletionRequest->approved_at);

        Notification::assertSentTo($requester, TransactionRejected::class);
    }
}
