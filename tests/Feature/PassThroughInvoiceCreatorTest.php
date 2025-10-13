<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Invoice;
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

        $service = app(PassThroughInvoiceCreator::class);

        $invoice = $service->create([
            'customer_type' => PassThroughPackage::CUSTOMER_TYPE_NEW,
            'daily_balance' => 30000,
            'estimated_duration' => 10,
            'maintenance_fee' => 50000,
            'account_creation_fee' => 75000,
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
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Jasa Maintenance',
            'price' => 50000,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Dana Pass Through (30.000 x 10 hari)',
            'price' => 300000,
        ]);

        $this->assertDatabaseHas('debts', [
            'invoice_id' => $invoice->id,
            'user_id' => $owner->id,
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 300000,
            'daily_deduction' => 30000,
            'status' => Debt::STATUS_BELUM_LUNAS,
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

        $service = app(PassThroughInvoiceCreator::class);

        $invoice = $service->create([
            'customer_type' => PassThroughPackage::CUSTOMER_TYPE_EXISTING,
            'daily_balance' => 20000,
            'estimated_duration' => 5,
            'maintenance_fee' => 30000,
            'account_creation_fee' => 50000,
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
        $this->assertSame(130000.0, (float) $invoice->total);
        $this->assertCount(2, $invoice->items);

        $this->assertDatabaseMissing('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Biaya Pembuatan Akun Iklan',
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Jasa Maintenance',
            'price' => 30000,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Dana Pass Through (20.000 x 5 hari)',
            'price' => 100000,
        ]);

        $this->assertDatabaseHas('debts', [
            'invoice_id' => $invoice->id,
            'user_id' => $owner->id,
            'amount' => 100000,
            'daily_deduction' => 20000,
        ]);

        $this->assertDatabaseHas('transactions', [
            'category_id' => $category->id,
            'amount' => 30000,
            'user_id' => $owner->id,
            'description' => 'Jasa Maintenance - CV Lancar',
        ]);

        $this->assertDatabaseMissing('transactions', [
            'category_id' => $category->id,
            'amount' => 50000,
            'description' => 'Biaya Pembuatan Akun - CV Lancar',
        ]);

        Carbon::setTestNow();
    }
}

