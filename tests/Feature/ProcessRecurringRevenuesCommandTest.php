<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RecurringRevenue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessRecurringRevenuesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_generates_transaction_and_invoice(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $revenue = RecurringRevenue::create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'amount' => 1000,
            'frequency' => 'monthly',
            'next_run' => now()->subDay(),
            'paused' => false,
            'description' => 'Subscription',
        ]);

        $this->artisan('recurring:process')->assertExitCode(0);

        $this->assertDatabaseHas('transactions', [
            'category_id' => $category->id,
            'user_id' => $user->id,
            'amount' => 1000,
            'description' => 'Subscription',
        ]);

        $this->assertDatabaseHas('invoices', [
            'recurring_revenue_id' => $revenue->id,
            'total' => 1000,
        ]);

        $revenue->refresh();
        $this->assertTrue($revenue->next_run->gt(now()));
    }
}
