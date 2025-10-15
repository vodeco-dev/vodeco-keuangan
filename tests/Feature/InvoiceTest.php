<?php

namespace Tests\Feature;

use App\Models\Category;
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

    public function test_user_can_send_and_mark_invoice_paid()
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Item',
            'quantity' => 1,
            'price' => 500,
        ]);

        $this->actingAs($user)->post(route('invoices.send', $invoice));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'belum bayar',
        ]);

        $category = Category::factory()->create(['type' => 'pemasukan']);

        $this->actingAs($user)->post(route('invoices.pay', $invoice), [
            'payment_amount' => $invoice->total,
            'payment_date' => now()->toDateString(),
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'lunas',
        ]);
    }

    public function test_can_view_invoice_publicly_with_valid_token()
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'issue_date' => now(),
        ]);

        $this->assertNotNull($invoice->public_token);

        $response = $this->get(route('invoices.public.show', ['token' => $invoice->public_token]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'inline; filename="' . $invoice->number . '.pdf"');
    }

    public function test_cannot_view_invoice_publicly_with_invalid_token()
    {
        $invalidToken = 'this-is-not-a-valid-uuid';
        $response = $this->get(route('invoices.public.show', ['token' => $invalidToken]));

        $response->assertStatus(404);
    }
}
