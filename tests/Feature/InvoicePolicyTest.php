<?php

namespace Tests\Feature;

use App\Models\Invoice;
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

        $invoice = Invoice::create([
            'user_id' => $user->id,
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

    public function test_user_can_view_invoice_list(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('viewAny', Invoice::class));
    }
}
