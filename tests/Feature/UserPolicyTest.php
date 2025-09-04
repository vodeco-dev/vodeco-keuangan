<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_manage_self(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->assertTrue($user->can('view', $user));
        $this->assertFalse($user->can('view', $other));
        $this->assertTrue($user->can('update', $user));
        $this->assertFalse($user->can('delete', $other));
    }
}
