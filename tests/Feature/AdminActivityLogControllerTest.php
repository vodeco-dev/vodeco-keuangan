<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_activity_logs(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $user = User::factory()->create();

        $log = ActivityLog::create([
            'user_id' => $user->id,
            'description' => 'Membuka halaman laporan',
            'method' => 'GET',
            'url' => '/reports',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.activity-logs.index'));

        $response->assertOk();
        $response->assertViewIs('admin.activity_logs.index');
        $response->assertViewHas('logs', function ($paginator) use ($log) {
            return $paginator->contains(fn ($item) => $item->id === $log->id);
        });
    }

    public function test_non_admin_cannot_access_activity_logs(): void
    {
        $user = User::factory()->create(['role' => Role::STAFF]);

        $response = $this->actingAs($user)->get(route('admin.activity-logs.index'));

        $response->assertForbidden();
    }
}
