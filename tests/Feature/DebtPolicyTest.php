<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Debt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_accountant_can_manage_other_users_debts(): void
    {
        $owner = User::factory()->create();
        $debt = Debt::factory()->for($owner)->create();

        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $accountant = User::factory()->create(['role' => Role::ACCOUNTANT]);

        $this->assertTrue($admin->can('view', $debt));
        $this->assertTrue($admin->can('update', $debt));
        $this->assertTrue($admin->can('delete', $debt));

        $this->assertTrue($accountant->can('view', $debt));
        $this->assertTrue($accountant->can('update', $debt));
        $this->assertTrue($accountant->can('delete', $debt));
    }

    public function test_non_privileged_users_cannot_manage_other_users_debts(): void
    {
        $owner = User::factory()->create();
        $debt = Debt::factory()->for($owner)->create();

        $otherStaff = User::factory()->create(['role' => Role::STAFF]);

        $this->assertFalse($otherStaff->can('view', $debt));
        $this->assertFalse($otherStaff->can('update', $debt));
        $this->assertFalse($otherStaff->can('delete', $debt));
    }
}
