<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        $category = Category::factory()->create(['type' => 'pemasukan']);

        $response = $this->actingAs($user)->post('/invoices', [
            'transaction_type' => 'down_payment',
            'client_name' => 'Client',
            'client_whatsapp' => '08123456789',
            'client_address' => 'Address',
            'number' => 'INV-001',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'items' => [
                [
                    'description' => 'Service',
                    'quantity' => 1,
                    'price' => 1000,
                    'category_id' => $category->id,
                ],
            ],
        ]);

        $response->assertRedirect('/invoices');
        $this->assertDatabaseHas('invoices', [
            'client_name' => 'Client',
            'total' => 1000,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('invoice_items', [
            'description' => 'Service',
            'quantity' => 1,
            'price' => 1000,
            'category_id' => $category->id,
        ]);
    }

    public function test_down_payment_invoice_does_not_trigger_pass_through_validation_errors(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['type' => 'pemasukan']);

        $response = $this->actingAs($user)
            ->from('/invoices/create')
            ->post('/invoices', [
                'transaction_type' => 'down_payment',
                'client_name' => 'Client Tanpa Pass Through',
                'client_whatsapp' => '08123456789',
                'client_address' => 'Alamat',
                'items' => [
                    [
                        'description' => 'Service',
                        'quantity' => 1,
                        'price' => 1000,
                        'category_id' => $category->id,
                    ],
                ],
            ]);

        $response->assertRedirect('/invoices');
        $response->assertSessionDoesntHaveErrors([
            'pass_through_custom_daily_balance' => 'Saldo harian paket custom minimal 1.',
        ]);
        $response->assertSessionHasNoErrors();
    }

    public function test_authenticated_user_can_create_full_payment_invoice(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['type' => 'pemasukan']);

        $response = $this->actingAs($user)->post('/invoices', [
            'transaction_type' => 'full_payment',
            'client_name' => 'Client Lunas',
            'client_whatsapp' => '08123456789',
            'client_address' => 'Address',
            'items' => [
                [
                    'description' => 'Service Lunas',
                    'quantity' => 1,
                    'price' => 250000,
                    'category_id' => $category->id,
                ],
            ],
        ]);

        $response->assertRedirect('/invoices');

        $invoice = Invoice::where('client_name', 'Client Lunas')->first();

        $this->assertNotNull($invoice);
        $this->assertSame('lunas', $invoice->status);
        $this->assertEquals(250000.0, (float) $invoice->total);
        $this->assertEquals(250000.0, (float) $invoice->down_payment);
        $this->assertNotNull($invoice->payment_date);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Service Lunas',
            'quantity' => 1,
            'price' => 250000,
            'category_id' => $category->id,
        ]);
    }

    public function test_authenticated_user_can_create_settlement_invoice(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['type' => 'pemasukan']);

        $referenceInvoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'total' => 2000000,
            'down_payment' => 500000,
        ]);

        InvoiceItem::create([
            'invoice_id' => $referenceInvoice->id,
            'category_id' => $category->id,
            'description' => 'Jasa Utama',
            'quantity' => 1,
            'price' => 2000000,
        ]);

        $remaining = 1500000;
        $paidAmount = 1500000;

        $response = $this->actingAs($user)->post('/invoices', [
            'transaction_type' => 'settlement',
            'settlement_invoice_number' => $referenceInvoice->number,
            'settlement_remaining_balance' => $remaining,
            'settlement_payment_status' => 'paid_full',
            'settlement_paid_amount' => $paidAmount,
        ]);

        $response->assertRedirect('/invoices');

        $settlementInvoiceId = Invoice::where('reference_invoice_id', $referenceInvoice->id)
            ->latest('id')
            ->value('id');

        $this->assertNotNull($settlementInvoiceId);

        $this->assertDatabaseHas('invoices', [
            'reference_invoice_id' => $referenceInvoice->id,
            'type' => Invoice::TYPE_SETTLEMENT,
            'total' => number_format($paidAmount, 2, '.', ''),
            'status' => 'lunas',
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $settlementInvoiceId,
            'description' => 'Pelunasan Invoice #' . $referenceInvoice->number,
            'price' => number_format($paidAmount, 2, '.', ''),
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $referenceInvoice->id,
            'down_payment' => number_format($referenceInvoice->down_payment + $paidAmount, 2, '.', ''),
            'status' => 'lunas',
        ]);
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

    public function test_invoice_creation_generates_pdf_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $category = Category::factory()->create(['type' => 'pemasukan']);

        $response = $this->actingAs($user)->post('/invoices', [
            'transaction_type' => 'down_payment',
            'client_name' => 'Client PDF',
            'client_whatsapp' => '081234567800',
            'client_address' => 'Alamat PDF',
            'items' => [
                [
                    'description' => 'Layanan PDF',
                    'quantity' => 1,
                    'price' => 50000,
                    'category_id' => $category->id,
                ],
            ],
        ]);

        $response->assertRedirect('/invoices');

        $invoice = Invoice::latest('id')->first();

        $this->assertNotNull($invoice);
        $this->assertNotNull($invoice->pdf_path);
        Storage::disk('public')->assertExists($invoice->pdf_path);
    }

    public function test_invoice_pdf_removed_when_invoice_deleted(): void
    {
        Storage::fake('public');

        $invoice = Invoice::factory()->create();

        $path = 'invoices/deletion-test.pdf';
        Storage::disk('public')->put($path, 'dummy-content');

        $invoice->forceFill(['pdf_path' => $path])->save();

        $invoice->delete();

        Storage::disk('public')->assertMissing($path);
    }

    public function test_invoice_pdf_service_regenerates_missing_file_before_returning_url(): void
    {
        Storage::fake('public');

        $invoice = Invoice::factory()->create();
        $category = Category::factory()->create(['type' => 'pemasukan']);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'category_id' => $category->id,
            'description' => 'Item Regenerasi',
            'quantity' => 1,
            'price' => 75000,
        ]);

        $service = app(InvoicePdfService::class);

        $initialPath = $service->store($invoice);
        $invoice->forceFill(['pdf_path' => $initialPath])->save();

        Storage::disk('public')->assertExists($initialPath);

        Storage::disk('public')->delete($initialPath);
        $this->assertFalse(Storage::disk('public')->exists($initialPath));

        $invoice->refresh();

        $url = $service->ensureHostedUrl($invoice);

        $this->assertNotNull($url);

        $invoice->refresh();
        $this->assertNotSame($initialPath, $invoice->pdf_path);
        Storage::disk('public')->assertExists($invoice->pdf_path);
        $this->assertSame(Storage::disk('public')->url($invoice->pdf_path), $url);
    }

    public function test_public_hosted_route_regenerates_missing_pdf(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
        ]);
        $category = Category::factory()->create(['type' => 'pemasukan']);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'category_id' => $category->id,
            'description' => 'Item Publik',
            'quantity' => 1,
            'price' => 125000,
        ]);

        $service = app(InvoicePdfService::class);
        $initialPath = $service->store($invoice);
        $invoice->forceFill(['pdf_path' => $initialPath])->save();

        Storage::disk('public')->assertExists($initialPath);

        Storage::disk('public')->delete($initialPath);
        $invoice->refresh();

        $response = $this->get(route('invoices.public.pdf-hosted', ['token' => $invoice->public_token]));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'inline; filename="' . $invoice->number . '.pdf"');

        $invoice->refresh();
        $this->assertNotSame($initialPath, $invoice->pdf_path);
        Storage::disk('public')->assertExists($invoice->pdf_path);
    }

    public function test_cannot_view_invoice_publicly_with_invalid_token()
    {
        $invalidToken = 'this-is-not-a-valid-uuid';
        $response = $this->get(route('invoices.public.show', ['token' => $invalidToken]));

        $response->assertStatus(404);
    }
}
