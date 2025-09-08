<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_can_only_manage_self(): void
    {
        $user = User::factory()->create(['role' => Role::STAFF]);
        $other = User::factory()->create(['role' => Role::STAFF]);

        $this->assertTrue($user->can('view', $user));
        $this->assertFalse($user->can('view', $other));
        $this->assertTrue($user->can('update', $user));
        $this->assertFalse($user->can('delete', $other));
    }

    public function test_admin_can_manage_other_users(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $other = User::factory()->create(['role' => Role::STAFF]);

        $this->assertTrue($admin->can('view', $other));
        $this->assertTrue($admin->can('update', $other));
        $this->assertTrue($admin->can('delete', $other));
    }
}
