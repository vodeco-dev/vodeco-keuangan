<?php

namespace Tests\Feature;

use App\Enums\InvoicePortalPassphraseAccessType;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoicePortalPassphrase;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Mockery;
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

        $pdfMock = Mockery::mock(DomPdf::class);
        $pdfMock->shouldReceive('setPaper')
            ->once()
            ->with('a4')
            ->andReturnSelf();
        $pdfMock->shouldReceive('download')
            ->once()
            ->with(Mockery::type('string'))
            ->andReturn(response('PDF content', 200, ['Content-Type' => 'application/pdf']));

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('invoices.pdf', Mockery::on(function ($data) {
                return isset($data['invoice'], $data['settings']);
            }))
            ->andReturn($pdfMock);

        Storage::fake('public');

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
            'payment_proof' => UploadedFile::fake()->image('bukti.png', 600, 600),
        ];

        $response = $this->withSession($session)->post(route('invoices.public.store'), $submissionData);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseHas('invoices', [
            'client_name' => 'John Doe',
            'status' => 'belum lunas',
            'customer_service_name' => $passphrase->displayLabel(),
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'description' => 'Layanan Konsultasi',
            'quantity' => 1,
            'price' => 1500000,
        ]);

        $invoice = Invoice::latest('id')->first();
        $this->assertNotNull($invoice?->payment_proof_path);
        Storage::disk('public')->assertExists($invoice->payment_proof_path);

        $passphrase->refresh();

        $this->assertSame(1, $passphrase->usage_count);
        $this->assertNotNull($passphrase->last_used_at);

        $this->assertDatabaseHas('invoice_portal_passphrase_logs', [
            'invoice_portal_passphrase_id' => $passphrase->id,
            'action' => 'submission',
        ]);
    }

    public function test_public_invoice_submission_with_full_payment_transaction_generates_paid_invoice(): void
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

        $pdfMock = Mockery::mock(DomPdf::class);
        $pdfMock->shouldReceive('setPaper')
            ->once()
            ->with('a4')
            ->andReturnSelf();
        $pdfMock->shouldReceive('download')
            ->once()
            ->with(Mockery::type('string'))
            ->andReturn(response('PDF content', 200, ['Content-Type' => 'application/pdf']));

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('invoices.pdf', Mockery::on(function ($data) {
                return isset($data['invoice'], $data['settings']);
            }))
            ->andReturn($pdfMock);

        Storage::fake('public');

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
            'payment_proof' => UploadedFile::fake()->image('bukti.png', 600, 600),
        ];

        $response = $this->withSession($session)->post(route('invoices.public.store'), $submissionData);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseHas('invoices', [
            'client_name' => 'Jane Doe',
            'status' => 'lunas',
            'down_payment' => 2500000,
        ]);

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
        $this->assertSame('Bayar Lunas', trim($transactionButtons->item(0)?->textContent ?? ''));

        $pdfMock = Mockery::mock(DomPdf::class);
        $pdfMock->shouldReceive('setPaper')
            ->once()
            ->with('a4')
            ->andReturnSelf();
        $pdfMock->shouldReceive('download')
            ->once()
            ->with(Mockery::type('string'))
            ->andReturn(response('PDF content', 200, ['Content-Type' => 'application/pdf']));

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('invoices.pdf', Mockery::on(function ($data) {
                return isset($data['invoice'], $data['settings']);
            }))
            ->andReturn($pdfMock);

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

        $response = $this->withSession($session)->post(route('invoices.public.store'), $submissionData);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseHas('invoices', [
            'client_name' => 'Siti Budi',
            'status' => 'lunas',
            'down_payment' => 1800000,
        ]);
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

        Storage::fake('public');

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
            'payment_proof' => UploadedFile::fake()->image('bukti.png', 600, 600),
        ];

        $response = $this->withSession($session)->post(route('invoices.public.store'), $submissionData);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'transaction_type' => 'Transaksi ini tidak diizinkan oleh passphrase yang digunakan.',
        ]);
    }
}
