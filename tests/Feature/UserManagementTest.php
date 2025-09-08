<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_user_role(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $user = User::factory()->create(['role' => Role::STAFF]);

        $response = $this->actingAs($admin)->patch(route('users.update', $user), [
            'role' => 'accountant',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertEquals(Role::ACCOUNTANT, $user->fresh()->role);
    }
}
