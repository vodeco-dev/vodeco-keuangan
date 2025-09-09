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
            'status' => 'Proses',
        ]);

        $this->actingAs($user)->post(route('invoices.pay', $invoice), [
            'payment_amount' => $invoice->total,
            'payment_date' => now()->toDateString(),
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'Terbayar',
        ]);
    }

    public function test_can_view_invoice_publicly_with_valid_token()
    {
        $invoice = Invoice::create([
            'number' => 'PUB-001',
            'issue_date' => now()->toDateString(),
            'status' => 'Proses',
            'total' => 12345,
            'client_name' => 'Public Client',
            'client_email' => 'public@example.com',
            'client_address' => 'Public Address',
        ]);

        $this->assertNotNull($invoice->public_token);

        $response = $this->get(route('invoices.public.show', ['token' => $invoice->public_token]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'inline; filename="PUB-001.pdf"');
    }

    public function test_cannot_view_invoice_publicly_with_invalid_token()
    {
        $invalidToken = 'this-is-not-a-valid-uuid';
        $response = $this->get(route('invoices.public.show', ['token' => $invalidToken]));

        $response->assertStatus(404);
    }
}
