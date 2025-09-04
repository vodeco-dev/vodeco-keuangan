<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RecurringRevenue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringRevenuePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_manage_own_recurring_revenue(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->create();

        $revenue = RecurringRevenue::create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'amount' => 1000,
            'frequency' => 'monthly',
            'next_run' => now()->addMonth(),
            'paused' => false,
            'description' => 'Test',
        ]);

        $this->assertTrue($user->can('view', $revenue));
        $this->assertFalse($other->can('view', $revenue));
        $this->assertTrue($user->can('update', $revenue));
        $this->assertFalse($other->can('delete', $revenue));
    }
}
