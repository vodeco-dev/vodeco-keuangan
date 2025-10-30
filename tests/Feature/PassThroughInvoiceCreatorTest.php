<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PassThroughInvoiceCreator;
use App\Support\PassThroughPackage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassThroughInvoiceCreatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_invoice_and_records_transactions_for_new_customer(): void
    {
        Carbon::setTestNow('2024-05-01 08:00:00');

        $owner = User::factory()->create(['role' => Role::ADMIN]);
        $creator = User::factory()->create();

        $category = Category::create([
            'name' => 'Penjualan Iklan',
            'type' => 'pemasukan',
        ]);

        Setting::updateOrCreate(
            ['key' => 'pass_through_invoice_category_id'],
            ['value' => $category->id]
        );

        $service = app(PassThroughInvoiceCreator::class);

        $package = new PassThroughPackage([
            'id' => 'pkg-new',
            'name' => 'Paket Startup',
            'customer_type' => PassThroughPackage::CUSTOMER_TYPE_NEW,
            'daily_balance' => 30000,
            'duration_days' => 10,
            'maintenance_fee' => 50000,
            'account_creation_fee' => 75000,
        ]);

        $invoice = $service->create($package, 1, [
            'description' => 'Kampanye Marketplace',
            'owner_id' => $owner->id,
            'created_by' => $creator->id,
            'customer_service_id' => null,
            'customer_service_name' => 'CS Andi',
            'client_name' => 'PT Maju Bersama',
            'client_whatsapp' => '08123456789',
            'client_address' => 'Jl. Mawar No. 1',
            'due_date' => Carbon::now()->addDays(7),
            'debt_user_id' => $owner->id,
        ]);

        $invoice = $invoice->fresh(['items', 'debt', 'owner']);

        $this->assertSame(Invoice::TYPE_PASS_THROUGH_NEW, $invoice->type);
        $this->assertSame(425000.0, (float) $invoice->total);
        $this->assertSame('CS Andi', $invoice->customer_service_name);
        $this->assertCount(3, $invoice->items);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Biaya Pembuatan Akun Iklan',
            'price' => 75000,
            'quantity' => 1,
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Jasa Maintenance',
            'price' => 50000,
            'quantity' => 1,
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Kampanye Marketplace – Dana Invoices Iklan (30.000 x 10 hari)',
            'price' => 300000,
            'quantity' => 1,
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('debts', [
            'invoice_id' => $invoice->id,
            'user_id' => $owner->id,
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 300000,
            'daily_deduction' => 30000,
            'status' => Debt::STATUS_BELUM_LUNAS,
            'description' => 'Invoices Iklan Kampanye Marketplace',
        ]);

        $this->assertDatabaseHas('transactions', [
            'category_id' => $category->id,
            'amount' => 50000,
            'user_id' => $owner->id,
            'description' => 'Jasa Maintenance - PT Maju Bersama',
        ]);

        $this->assertDatabaseHas('transactions', [
            'category_id' => $category->id,
            'amount' => 75000,
            'user_id' => $owner->id,
            'description' => 'Biaya Pembuatan Akun - PT Maju Bersama',
        ]);

        Carbon::setTestNow();
    }

    public function test_existing_customer_invoice_ignores_account_creation_fee(): void
    {
        Carbon::setTestNow('2024-05-02 09:00:00');

        $owner = User::factory()->create(['role' => Role::ADMIN]);
        $creator = User::factory()->create();

        $category = Category::create([
            'name' => 'Penjualan Iklan',
            'type' => 'pemasukan',
        ]);

        Setting::updateOrCreate(
            ['key' => 'pass_through_invoice_category_id'],
            ['value' => $category->id]
        );

        $service = app(PassThroughInvoiceCreator::class);

        $package = new PassThroughPackage([
            'id' => 'pkg-existing',
            'name' => 'Paket Reguler',
            'customer_type' => PassThroughPackage::CUSTOMER_TYPE_EXISTING,
            'daily_balance' => 20000,
            'duration_days' => 5,
            'maintenance_fee' => 30000,
            'account_creation_fee' => 50000,
        ]);

        $invoice = $service->create($package, 2, [
            'description' => 'Promo Ramadhan',
            'owner_id' => $owner->id,
            'created_by' => $creator->id,
            'customer_service_id' => null,
            'customer_service_name' => 'CS Budi',
            'client_name' => 'CV Lancar',
            'client_whatsapp' => '08999888777',
            'client_address' => 'Jl. Melati No. 2',
            'due_date' => Carbon::now()->addDays(3),
            'debt_user_id' => $owner->id,
        ]);

        $invoice = $invoice->fresh(['items', 'debt']);

        $this->assertSame(Invoice::TYPE_PASS_THROUGH_EXISTING, $invoice->type);
        $this->assertSame(260000.0, (float) $invoice->total);
        $this->assertCount(2, $invoice->items);

        $this->assertDatabaseMissing('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Biaya Pembuatan Akun Iklan',
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Jasa Maintenance',
            'price' => 30000,
            'quantity' => 2,
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Promo Ramadhan – Dana Invoices Iklan (20.000 x 5 hari)',
            'price' => 100000,
            'quantity' => 2,
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('debts', [
            'invoice_id' => $invoice->id,
            'user_id' => $owner->id,
            'amount' => 200000,
            'daily_deduction' => 40000,
            'description' => 'Invoices Iklan Promo Ramadhan (x2)',
        ]);

        $this->assertDatabaseHas('transactions', [
            'category_id' => $category->id,
            'amount' => 60000,
            'user_id' => $owner->id,
            'description' => 'Jasa Maintenance (x2) - CV Lancar',
        ]);

        $this->assertDatabaseMissing('transactions', [
            'category_id' => $category->id,
            'amount' => 50000,
            'description' => 'Biaya Pembuatan Akun - CV Lancar',
        ]);

        Carbon::setTestNow();
    }

    public function test_store_invoice_requires_custom_fields(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::ADMIN]));

        $response = $this->post(route('invoices.store'), [
            'transaction_type' => 'pass_through',
            'client_name' => 'PT Tanpa Data',
            'client_whatsapp' => '081234567890',
            'client_address' => 'Jl. Mawar No. 9',
            'pass_through_package_id' => 'custom',
            'pass_through_quantity' => '1',
            'pass_through_custom_customer_type' => 'new',
        ]);

        $response->assertSessionHasErrors([
            'pass_through_custom_daily_balance',
            'pass_through_custom_duration_days',
            'pass_through_custom_maintenance_fee',
            'pass_through_custom_account_creation_fee',
        ]);
    }

    public function test_store_invoice_with_custom_package_creates_records(): void
    {
        Carbon::setTestNow('2024-06-01 09:00:00');

        $owner = User::factory()->create(['role' => Role::ADMIN]);

        $category = Category::create([
            'name' => 'Penjualan Iklan',
            'type' => 'pemasukan',
        ]);

        Setting::updateOrCreate(
            ['key' => 'pass_through_invoice_category_id'],
            ['value' => $category->id]
        );

        $this->actingAs($owner);

        $response = $this->post(route('invoices.store'), [
            'transaction_type' => 'pass_through',
            'client_name' => 'PT Custom Mandiri',
            'client_whatsapp' => '08120000000',
            'client_address' => 'Jl. Kenanga No. 5',
            'pass_through_package_id' => 'custom',
            'pass_through_quantity' => '2',
            'pass_through_custom_customer_type' => 'new',
            'pass_through_custom_daily_balance' => '100.000',
            'pass_through_custom_duration_days' => '10',
            'pass_through_custom_maintenance_fee' => '200.000',
            'pass_through_custom_account_creation_fee' => '300.000',
            'pass_through_daily_balance_unit' => '100.000',
            'pass_through_ad_budget_unit' => '1.000.000',
            'pass_through_maintenance_unit' => '200.000',
            'pass_through_account_creation_unit' => '300.000',
            'pass_through_ad_budget_total' => '2.000.000',
            'pass_through_maintenance_total' => '400.000',
            'pass_through_account_creation_total' => '600.000',
            'pass_through_total_price' => '3.000.000',
            'pass_through_daily_balance_total' => '200.000',
            'pass_through_duration_days' => '10',
        ]);

        $response->assertRedirect(route('invoices.index'));

        $invoice = Invoice::with('items', 'debt')->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertSame(Invoice::TYPE_PASS_THROUGH_NEW, $invoice->type);
        $this->assertSame(3000000.0, (float) $invoice->total);
        $this->assertCount(3, $invoice->items);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Biaya Pembuatan Akun Iklan',
            'price' => 300000,
            'quantity' => 2,
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Jasa Maintenance',
            'price' => 200000,
            'quantity' => 2,
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Paket Custom – Dana Invoices Iklan (100.000 x 10 hari)',
            'price' => 1000000,
            'quantity' => 2,
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('debts', [
            'invoice_id' => $invoice->id,
            'user_id' => $owner->id,
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 2000000,
            'daily_deduction' => 200000,
            'description' => 'Invoices Iklan Paket Custom (x2)',
        ]);

        $this->assertDatabaseHas('transactions', [
            'category_id' => $category->id,
            'amount' => 400000,
            'user_id' => $owner->id,
            'description' => 'Jasa Maintenance (x2) - PT Custom Mandiri',
        ]);

        $this->assertDatabaseHas('transactions', [
            'category_id' => $category->id,
            'amount' => 600000,
            'user_id' => $owner->id,
            'description' => 'Biaya Pembuatan Akun (x2) - PT Custom Mandiri',
        ]);

        Carbon::setTestNow();
    }
}

