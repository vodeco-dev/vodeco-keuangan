<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Setting;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoicePdfServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoicePdfService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoicePdfService();
        Storage::fake('public');
    }

    public function test_view_data_returns_invoice_and_settings(): void
    {
        $invoice = Invoice::factory()->create();
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);
        Setting::create(['key' => 'test_key', 'value' => 'test_value']);

        $data = $this->service->viewData($invoice);

        $this->assertArrayHasKey('invoice', $data);
        $this->assertArrayHasKey('settings', $data);
        $this->assertInstanceOf(Invoice::class, $data['invoice']);
        $this->assertArrayHasKey('test_key', $data['settings']);
    }

    public function test_store_creates_pdf_file(): void
    {
        $invoice = Invoice::factory()->create(['number' => 'INV-001']);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $path = $this->service->store($invoice);

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString('inv-001', strtolower($path));
    }

    public function test_store_in_cache_creates_cached_pdf(): void
    {
        Config::set('pdf.cache.enabled', true);
        Config::set('pdf.cache.disk', 'public');
        Config::set('pdf.cache.path', 'invoices/cache');

        $invoice = Invoice::factory()->create(['number' => 'INV-002']);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $path = $this->service->storeInCache($invoice);

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString('invoices/cache', $path);
    }

    public function test_invalidate_cache_removes_cached_file(): void
    {
        Config::set('pdf.cache.enabled', true);
        Config::set('pdf.cache.disk', 'public');
        Config::set('pdf.cache.path', 'invoices/cache');

        $invoice = Invoice::factory()->create(['number' => 'INV-003']);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $path = $this->service->storeInCache($invoice);
        Storage::disk('public')->assertExists($path);

        $this->service->invalidateCache($invoice);

        Storage::disk('public')->assertMissing($path);
    }

    public function test_ensure_stored_pdf_path_returns_existing_path(): void
    {
        Config::set('pdf.generation.strategy', 'on_demand');
        Config::set('pdf.cache.enabled', true);

        $invoice = Invoice::factory()->create(['number' => 'INV-004']);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $path1 = $this->service->ensureStoredPdfPath($invoice);
        $path2 = $this->service->ensureStoredPdfPath($invoice);

        $this->assertEquals($path1, $path2);
    }

    public function test_ensure_hosted_url_returns_valid_url(): void
    {
        $invoice = Invoice::factory()->create(['number' => 'INV-005']);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $url = $this->service->ensureHostedUrl($invoice);

        $this->assertNotNull($url);
        $this->assertIsString($url);
    }
}

