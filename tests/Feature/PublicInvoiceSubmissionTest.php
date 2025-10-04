<?php

namespace Tests\Feature;

use App\Enums\InvoicePortalPassphraseAccessType;
use App\Enums\Role;
use App\Models\Category;
use App\Models\InvoicePortalPassphrase;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
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

        $passphrase->refresh();

        $this->assertSame(1, $passphrase->usage_count);
        $this->assertNotNull($passphrase->last_used_at);

        $this->assertDatabaseHas('invoice_portal_passphrase_logs', [
            'invoice_portal_passphrase_id' => $passphrase->id,
            'action' => 'submission',
        ]);
    }
}
