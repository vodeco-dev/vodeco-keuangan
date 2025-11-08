<?php

namespace Tests\Feature;

use App\Enums\InvoicePortalPassphraseAccessType;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoicePortalPassphrase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicInvoiceSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_invoice_submission_without_javascript_uses_default_transaction_type(): void
    {
        $admin = User::factory()->create([
            'role' => Role::ADMIN,
        ]);

        $category = Category::factory()->create([
            'type' => 'pemasukan',
        ]);

        $passphrase = new InvoicePortalPassphrase([
            'public_id' => InvoicePortalPassphrase::makePublicId(),
            'access_type' => InvoicePortalPassphraseAccessType::CUSTOMER_SERVICE,
            'label' => 'Tim CS',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $passphrase->setPassphrase('RahasiaPassphrase123');
        $passphrase->save();

        $session = [
            'invoice_portal_passphrase' => [
                'id' => $passphrase->id,
                'token' => Crypt::encryptString((string) $passphrase->id),
                'access_type' => $passphrase->access_type->value,
                'access_label' => $passphrase->access_type->label(),
                'label' => $passphrase->label,
                'display_label' => $passphrase->displayLabel(),
                'verified_at' => now()->toIso8601String(),
            ],
        ];

        $response = $this->withSession($session)->get(route('invoices.public.create'));
        $response->assertOk();

        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());

        $xpath = new \DOMXPath($dom);
        $transactionInputs = $xpath->query('//input[@name="transaction_type"]');

        $this->assertSame(1, $transactionInputs->length);

        $transactionInput = $transactionInputs->item(0);
        $defaultTransaction = $transactionInput?->getAttribute('value');
        $this->assertNotEmpty($defaultTransaction);

        $submissionData = [
            'passphrase_token' => $session['invoice_portal_passphrase']['token'],
            'transaction_type' => $defaultTransaction,
            'client_name' => 'John Doe',
            'client_whatsapp' => '08123456789',
            'client_address' => 'Jl. Mawar No. 1',
            'due_date' => now()->addWeek()->toDateString(),
            'items' => [
                [
                    'description' => 'Layanan Konsultasi',
                    'quantity' => 1,
                    'price' => 1500000,
                    'category_id' => $category->id,
                ],
            ],
        ];

        Storage::fake('public');

        $response = $this->withSession($session)->post(route('invoices.public.store'), $submissionData);

        $response->assertRedirect(route('invoices.public.create'));
        $response->assertSessionHas('invoice_pdf_url');
        $response->assertSessionHas('invoice_number');

        $invoice = Invoice::latest('id')->firstOrFail();

        $expectedPreviewUrl = route('invoices.public.pdf-hosted', ['token' => $invoice->public_token]);
        $response->assertSessionHas('invoice_pdf_url', $expectedPreviewUrl);

        $this->assertSame('belum lunas', $invoice->status);
        $this->assertFalse($invoice->needs_confirmation);
        $this->assertNull($invoice->payment_proof_path);
        $this->assertSame($passphrase->displayLabel(), $invoice->customer_service_name);

        Storage::disk('public')->assertExists($invoice->pdf_path);

        $previewResponse = $this->get($expectedPreviewUrl);
        $previewResponse->assertOk();
        $previewResponse->assertHeader('Content-Disposition', 'inline; filename="' . $invoice->number . '.pdf"');

        $this->assertDatabaseHas('invoice_items', [
            'description' => 'Layanan Konsultasi',
            'quantity' => 1,
            'price' => 1500000,
        ]);

        $passphrase->refresh();

        $this->assertSame(1, $passphrase->usage_count);
        $this->assertNotNull($passphrase->last_used_at);

        $this->assertDatabaseHas('invoice_portal_passphrase_logs', [
            'invoice_portal_passphrase_id' => $passphrase->id,
            'action' => 'submission',
        ]);
    }

    public function test_public_invoice_submission_with_full_payment_transaction_requires_confirmation(): void
    {
        $admin = User::factory()->create([
            'role' => Role::ADMIN,
        ]);

        $category = Category::factory()->create([
            'type' => 'pemasukan',
        ]);

        $passphrase = new InvoicePortalPassphrase([
            'public_id' => InvoicePortalPassphrase::makePublicId(),
            'access_type' => InvoicePortalPassphraseAccessType::CUSTOMER_SERVICE,
            'label' => 'Tim CS',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $passphrase->setPassphrase('RahasiaPassphrase123');
        $passphrase->save();

        $session = [
            'invoice_portal_passphrase' => [
                'id' => $passphrase->id,
                'token' => Crypt::encryptString((string) $passphrase->id),
                'access_type' => $passphrase->access_type->value,
                'access_label' => $passphrase->access_type->label(),
                'label' => $passphrase->label,
                'display_label' => $passphrase->displayLabel(),
                'verified_at' => now()->toIso8601String(),
            ],
        ];

        $submissionData = [
            'passphrase_token' => $session['invoice_portal_passphrase']['token'],
            'transaction_type' => 'full_payment',
            'client_name' => 'Jane Doe',
            'client_whatsapp' => '08111111111',
            'client_address' => 'Jl. Melati No. 2',
            'due_date' => now()->addWeek()->toDateString(),
            'items' => [
                [
                    'description' => 'Pembuatan Website',
                    'quantity' => 1,
                    'price' => 2500000,
                    'category_id' => $category->id,
                ],
            ],
        ];

        Storage::fake('public');

        $response = $this->withSession($session)->post(route('invoices.public.store'), $submissionData);

        $response->assertRedirect(route('invoices.public.create'));
        $response->assertSessionHas('invoice_pdf_url');

        $invoice = Invoice::where('client_name', 'Jane Doe')->latest('id')->firstOrFail();
        $expectedPreviewUrl = route('invoices.public.pdf-hosted', ['token' => $invoice->public_token]);

        $response->assertSessionHas('invoice_pdf_url', $expectedPreviewUrl);

        $this->assertSame('belum lunas', $invoice->status);
        $this->assertTrue($invoice->needs_confirmation);
        $this->assertSame(0.0, (float) $invoice->down_payment);
        $this->assertNull($invoice->payment_date);

        Storage::disk('public')->assertExists($invoice->pdf_path);

        $previewResponse = $this->get($expectedPreviewUrl);
        $previewResponse->assertOk();
        $previewResponse->assertHeader('Content-Disposition', 'inline; filename="' . $invoice->number . '.pdf"');

        $this->assertDatabaseHas('invoice_items', [
            'description' => 'Pembuatan Website',
            'quantity' => 1,
            'price' => 2500000,
        ]);
    }

    public function test_admin_perpanjangan_passphrase_only_allows_full_payment_transactions(): void
    {
        $admin = User::factory()->create([
            'role' => Role::ADMIN,
        ]);

        $category = Category::factory()->create([
            'type' => 'pemasukan',
        ]);

        $passphrase = new InvoicePortalPassphrase([
            'public_id' => InvoicePortalPassphrase::makePublicId(),
            'access_type' => InvoicePortalPassphraseAccessType::ADMIN_PERPANJANGAN,
            'label' => 'Tim Perpanjangan',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $passphrase->setPassphrase('RahasiaPerpanjangan12');
        $passphrase->save();

        $session = [
            'invoice_portal_passphrase' => [
                'id' => $passphrase->id,
                'token' => Crypt::encryptString((string) $passphrase->id),
                'access_type' => $passphrase->access_type->value,
                'access_label' => $passphrase->access_type->label(),
                'label' => $passphrase->label,
                'display_label' => $passphrase->displayLabel(),
                'verified_at' => now()->toIso8601String(),
            ],
        ];

        $response = $this->withSession($session)->get(route('invoices.public.create'));
        $response->assertOk();

        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());

        $xpath = new \DOMXPath($dom);
        $transactionInput = $xpath->query('//input[@name="transaction_type"]')->item(0);
        $this->assertSame('full_payment', $transactionInput?->getAttribute('value'));

        $transactionButtons = $xpath->query("//h3[text()='Jenis Transaksi']/following-sibling::div[1]//button");
        $this->assertSame(1, $transactionButtons->length);
        $this->assertSame('Menunggu Pembayaran', trim($transactionButtons->item(0)?->textContent ?? ''));

        $submissionData = [
            'passphrase_token' => $session['invoice_portal_passphrase']['token'],
            'transaction_type' => 'full_payment',
            'client_name' => 'Siti Budi',
            'client_whatsapp' => '081222333444',
            'client_address' => 'Jl. Kenanga No. 3',
            'due_date' => now()->addWeek()->toDateString(),
            'items' => [
                [
                    'description' => 'Layanan Perpanjangan',
                    'quantity' => 1,
                    'price' => 1800000,
                    'category_id' => $category->id,
                ],
            ],
            'payment_proof' => UploadedFile::fake()->image('bukti.png', 600, 600),
        ];

        Storage::fake('public');

        $response = $this->withSession($session)->post(route('invoices.public.store'), $submissionData);

        $response->assertRedirect(route('invoices.public.create'));
        $response->assertSessionHas('invoice_pdf_url');

        $invoice = Invoice::where('client_name', 'Siti Budi')->latest('id')->firstOrFail();

        $this->assertSame('belum lunas', $invoice->status);
        $this->assertTrue($invoice->needs_confirmation);
        $this->assertSame(0.0, (float) $invoice->down_payment);
        $this->assertNull($invoice->payment_date);

        Storage::disk('public')->assertExists($invoice->pdf_path);
    }

    public function test_admin_perpanjangan_passphrase_rejects_non_full_payment_transactions(): void
    {
        $admin = User::factory()->create([
            'role' => Role::ADMIN,
        ]);

        $category = Category::factory()->create([
            'type' => 'pemasukan',
        ]);

        $passphrase = new InvoicePortalPassphrase([
            'public_id' => InvoicePortalPassphrase::makePublicId(),
            'access_type' => InvoicePortalPassphraseAccessType::ADMIN_PERPANJANGAN,
            'label' => 'Tim Perpanjangan',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $passphrase->setPassphrase('RahasiaPerpanjangan12');
        $passphrase->save();

        $session = [
            'invoice_portal_passphrase' => [
                'id' => $passphrase->id,
                'token' => Crypt::encryptString((string) $passphrase->id),
                'access_type' => $passphrase->access_type->value,
                'access_label' => $passphrase->access_type->label(),
                'label' => $passphrase->label,
                'display_label' => $passphrase->displayLabel(),
                'verified_at' => now()->toIso8601String(),
            ],
        ];

        $submissionData = [
            'passphrase_token' => $session['invoice_portal_passphrase']['token'],
            'transaction_type' => 'down_payment',
            'client_name' => 'Agus Tono',
            'client_whatsapp' => '081555666777',
            'client_address' => 'Jl. Flamboyan No. 5',
            'due_date' => now()->addWeek()->toDateString(),
            'down_payment_due' => 900000,
            'items' => [
                [
                    'description' => 'Layanan Perpanjangan Tahunan',
                    'quantity' => 1,
                    'price' => 1800000,
                    'category_id' => $category->id,
                ],
            ],
        ];

        $response = $this->withSession($session)->post(route('invoices.public.store'), $submissionData);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'transaction_type' => 'Transaksi ini tidak diizinkan oleh passphrase yang digunakan.',
        ]);
    }

    public function test_admin_pelunasan_passphrase_allows_full_payment_and_settlement_transactions(): void
    {
        $admin = User::factory()->create([
            'role' => Role::ADMIN,
        ]);

        $category = Category::factory()->create([
            'type' => 'pemasukan',
        ]);

        $passphrase = new InvoicePortalPassphrase([
            'public_id' => InvoicePortalPassphrase::makePublicId(),
            'access_type' => InvoicePortalPassphraseAccessType::ADMIN_PELUNASAN,
            'label' => 'Tim Pelunasan',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $passphrase->setPassphrase('RahasiaPelunasan12');
        $passphrase->save();

        $session = [
            'invoice_portal_passphrase' => [
                'id' => $passphrase->id,
                'token' => Crypt::encryptString((string) $passphrase->id),
                'access_type' => $passphrase->access_type->value,
                'access_label' => $passphrase->access_type->label(),
                'label' => $passphrase->label,
                'display_label' => $passphrase->displayLabel(),
                'verified_at' => now()->toIso8601String(),
            ],
        ];

        $response = $this->withSession($session)->get(route('invoices.public.create'));
        $response->assertOk();

        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());

        $xpath = new \DOMXPath($dom);
        $transactionInput = $xpath->query('//input[@name="transaction_type"]')->item(0);
        // Default should be first allowed transaction (settlement)
        $this->assertContains($transactionInput?->getAttribute('value'), ['settlement', 'full_payment']);

        $transactionButtons = $xpath->query("//h3[text()='Jenis Transaksi']/following-sibling::div[1]//button");
        $this->assertSame(2, $transactionButtons->length);
        
        $buttonTexts = [];
        foreach ($transactionButtons as $button) {
            $buttonTexts[] = trim($button->textContent ?? '');
        }
        $this->assertContains('Menunggu Pembayaran', $buttonTexts);
        $this->assertContains('Pelunasan', $buttonTexts);

        // Test creating invoice with full_payment
        $submissionData = [
            'passphrase_token' => $session['invoice_portal_passphrase']['token'],
            'transaction_type' => 'full_payment',
            'client_name' => 'Budi Santoso',
            'client_whatsapp' => '081333444555',
            'client_address' => 'Jl. Mawar No. 10',
            'due_date' => now()->addWeek()->toDateString(),
            'items' => [
                [
                    'description' => 'Layanan Pelunasan',
                    'quantity' => 1,
                    'price' => 2000000,
                    'category_id' => $category->id,
                ],
            ],
            'payment_proof' => UploadedFile::fake()->image('bukti.png', 600, 600),
        ];

        Storage::fake('public');

        $response = $this->withSession($session)->post(route('invoices.public.store'), $submissionData);

        $response->assertRedirect(route('invoices.public.create'));
        $response->assertSessionHas('invoice_pdf_url');

        $invoice = Invoice::where('client_name', 'Budi Santoso')->latest('id')->firstOrFail();

        $this->assertSame('belum lunas', $invoice->status);
        $this->assertTrue($invoice->needs_confirmation);
        $this->assertSame(0.0, (float) $invoice->down_payment);
        $this->assertNull($invoice->payment_date);

        Storage::disk('public')->assertExists($invoice->pdf_path);
    }

    public function test_public_payment_confirmation_uploads_payment_proof(): void
    {
        $admin = User::factory()->create([
            'role' => Role::ADMIN,
        ]);

        $passphrase = new InvoicePortalPassphrase([
            'public_id' => InvoicePortalPassphrase::makePublicId(),
            'access_type' => InvoicePortalPassphraseAccessType::CUSTOMER_SERVICE,
            'label' => 'Tim CS',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $passphrase->setPassphrase('RahasiaPassphrase123');
        $passphrase->save();

        $session = [
            'invoice_portal_passphrase' => [
                'id' => $passphrase->id,
                'token' => Crypt::encryptString((string) $passphrase->id),
                'access_type' => $passphrase->access_type->value,
                'access_label' => $passphrase->access_type->label(),
                'label' => $passphrase->label,
                'display_label' => $passphrase->displayLabel(),
                'verified_at' => now()->toIso8601String(),
            ],
        ];

        $invoice = Invoice::factory()->create([
            'user_id' => $admin->id,
            'created_by' => $admin->id,
            'customer_service_name' => $passphrase->displayLabel(),
            'status' => 'belum lunas',
            'total' => 2500000,
            'down_payment' => 0,
        ]);

        Storage::fake('public');

        $response = $this->withSession($session)->post(route('invoices.public.payment-confirm'), [
            'passphrase_token' => $session['invoice_portal_passphrase']['token'],
            'invoice_number' => $invoice->number,
            'payment_proof' => UploadedFile::fake()->image('bukti.png', 600, 600),
        ]);

        $response->assertRedirect(route('invoices.public.create'));
        $response->assertSessionHas('status', 'Bukti pembayaran berhasil dikirim. Tim akuntansi akan memverifikasi dalam waktu dekat.');
        $response->assertSessionHas('active_portal_tab', 'confirm_payment');

        $invoice->refresh();

        $this->assertNotNull($invoice->payment_proof_path);
        Storage::disk('public')->assertExists($invoice->payment_proof_path);

        $passphrase->refresh();
        $this->assertSame(1, $passphrase->usage_count);
        $this->assertNotNull($passphrase->last_used_at);

        $this->assertDatabaseHas('invoice_portal_passphrase_logs', [
            'invoice_portal_passphrase_id' => $passphrase->id,
            'action' => 'payment_confirmation',
        ]);
    }

    public function test_authenticated_user_can_view_payment_proof_via_secure_route(): void
    {
        $admin = User::factory()->create([
            'role' => Role::ADMIN,
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $admin->id,
            'created_by' => $admin->id,
            'payment_proof_disk' => 'public',
            'payment_proof_path' => 'invoice-proofs/test-proof.png',
            'payment_proof_filename' => 'test-proof.png',
        ]);

        Storage::fake('public');
        $fakeImage = UploadedFile::fake()->image('proof.png', 300, 300);
        Storage::disk('public')->put($invoice->payment_proof_path, file_get_contents($fakeImage->getRealPath()));

        $response = $this->actingAs($admin)->get(route('invoices.payment-proof.show', $invoice));

        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');
    }
}
