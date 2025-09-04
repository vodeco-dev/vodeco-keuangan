<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_invoices()
    {
        $response = $this->get('/invoices');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_create_invoice_with_items()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/invoices', [
            'client_name' => 'Client',
            'client_email' => 'client@example.com',
            'client_address' => 'Address',
            'number' => 'INV-001',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'items' => [
                [
                    'description' => 'Service',
                    'quantity' => 1,
                    'price' => 1000,
                ],
            ],
        ]);

        $response->assertRedirect('/invoices');
        $this->assertDatabaseHas('invoices', [
            'number' => 'INV-001',
            'client_name' => 'Client',
            'total' => 1000,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('invoice_items', [
            'description' => 'Service',
            'quantity' => 1,
            'price' => 1000,
        ]);
    }

    public function test_user_can_send_and_mark_invoice_paid()
    {
        $user = User::factory()->create();
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'number' => 'INV-100',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'Draft',
            'total' => 500,
            'client_name' => 'Client',
            'client_email' => 'client@example.com',
            'client_address' => 'Address',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Item',
            'quantity' => 1,
            'price' => 500,
        ]);

        $this->actingAs($user)->post(route('invoices.send', $invoice));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'Sent',
        ]);

        $this->actingAs($user)->post(route('invoices.pay', $invoice));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'Paid',
        ]);
    }
}
