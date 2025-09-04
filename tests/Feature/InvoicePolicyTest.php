<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\RecurringRevenue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_view_own_invoice(): void
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

        $invoice = Invoice::create([
            'recurring_revenue_id' => $revenue->id,
            'number' => 'INV-1',
            'issue_date' => now(),
            'due_date' => now()->addDay(),
            'status' => 'Draft',
            'total' => 1000,
            'client_name' => 'Client',
            'client_email' => 'client@example.com',
            'client_address' => 'Address',
        ]);

        $this->assertTrue($user->can('view', $invoice));
        $this->assertFalse($other->can('view', $invoice));
    }
}
