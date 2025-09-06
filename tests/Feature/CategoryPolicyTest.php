<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_own_category(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->make();
        $category->user_id = $user->id;

        $this->assertTrue($user->can('view', $category));
        $this->assertTrue($user->can('update', $category));
        $this->assertTrue($user->can('delete', $category));

        $this->assertFalse($other->can('view', $category));
        $this->assertFalse($other->can('update', $category));
        $this->assertFalse($other->can('delete', $category));
    }

    public function test_admin_can_manage_any_category(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $user = User::factory()->create();
        $category = Category::factory()->make();
        $category->user_id = $user->id;

        $this->assertTrue($admin->can('view', $category));
        $this->assertTrue($admin->can('update', $category));
        $this->assertTrue($admin->can('delete', $category));
    }

    public function test_only_admin_or_accountant_can_create_categories(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $accountant = User::factory()->create(['role' => Role::ACCOUNTANT]);
        $staff = User::factory()->create(['role' => Role::STAFF]);

        $this->assertTrue($admin->can('create', Category::class));
        $this->assertTrue($accountant->can('create', Category::class));
        $this->assertFalse($staff->can('create', Category::class));
    }
}

