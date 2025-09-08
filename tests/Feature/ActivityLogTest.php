<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_request_is_logged_with_description(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['type' => 'pemasukan']);

        $this->actingAs($user)->post('/transactions', [
            'date' => now()->toDateString(),
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $log = ActivityLog::latest()->first();
        $this->assertEquals('Menambahkan transactions', $log->description);
    }

    public function test_delete_request_is_logged_with_description(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $transaction = Transaction::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)->delete('/transactions/'.$transaction->id);

        $log = ActivityLog::latest()->first();
        $this->assertEquals('Menghapus transactions/'.$transaction->id, $log->description);
    }
}
