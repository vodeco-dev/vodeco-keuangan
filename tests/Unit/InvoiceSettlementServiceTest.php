<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InvoiceSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceSettlementService();
    }

    public function test_refresh_token_generates_new_token(): void
    {
        $invoice = Invoice::factory()->create([
            'settlement_token' => null,
            'settlement_token_expires_at' => null,
        ]);

        $result = $this->service->refreshToken($invoice);

        $this->assertNotNull($result->settlement_token);
        $this->assertNotNull($result->settlement_token_expires_at);
        $this->assertEquals(64, strlen($result->settlement_token));
    }

    public function test_refresh_token_uses_custom_expiry(): void
    {
        $invoice = Invoice::factory()->create();
        $expiry = now()->addDays(14);

        $result = $this->service->refreshToken($invoice, $expiry->toIso8601String());

        $this->assertEquals($expiry->format('Y-m-d H:i:s'), $result->settlement_token_expires_at->format('Y-m-d H:i:s'));
    }

    public function test_revoke_token_clears_token(): void
    {
        $invoice = Invoice::factory()->create([
            'settlement_token' => Str::random(64),
            'settlement_token_expires_at' => now()->addDay(),
        ]);

        $this->service->revokeToken($invoice);

        $invoice->refresh();
        $this->assertNull($invoice->settlement_token);
        $this->assertNull($invoice->settlement_token_expires_at);
    }

    public function test_confirm_settlement_creates_debt_and_transaction(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['type' => 'pemasukan']);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'total' => 1000,
            'down_payment' => 300,
            'status' => 'belum lunas',
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'category_id' => $category->id,
        ]);

        $this->service->confirmSettlement($invoice, '127.0.0.1', '/test-url');

        $invoice->refresh();
        
        // Logika baru: Payment dibuat = currentDownPayment (300) + remainingBalance (700) = 1000
        // Total payments = 1000, status menjadi 'lunas' karena 1000 >= 1000
        
        $debt = Debt::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($debt);
        
        $payment = Payment::where('debt_id', $debt->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(1000, $payment->amount);
        
        $totalPayments = $debt->payments()->sum('amount');
        $this->assertEquals(1000, $totalPayments);
        
        $this->assertEquals('lunas', $invoice->status);
        $this->assertEquals(1000, (float) $invoice->down_payment);
        $this->assertNotNull($invoice->payment_date);
        
        $debt->refresh();
        $this->assertEquals(Debt::STATUS_LUNAS, $debt->status);
        
        $transaction = Transaction::where('description', $invoice->transactionDescription())->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(1000, (float) $transaction->amount);

        $activityLog = ActivityLog::where('description', 'like', '%Konfirmasi pelunasan invoice%')->first();
        $this->assertNotNull($activityLog);
        $this->assertEquals('127.0.0.1', $activityLog->ip_address);
    }

    public function test_confirm_settlement_handles_full_down_payment(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['type' => 'pemasukan']);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'total' => 1000,
            'down_payment' => 1000,
            'status' => 'belum lunas',
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'category_id' => $category->id,
        ]);

        $this->service->confirmSettlement($invoice, '127.0.0.1', '/test-url');

        $invoice->refresh();
        
        // Logika baru: Payment dibuat = currentDownPayment (1000) + remainingBalance (0) = 1000
        // Total payments = 1000, status menjadi 'lunas' karena 1000 >= 1000
        
        $debt = Debt::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($debt);

        $payment = Payment::where('debt_id', $debt->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(1000, $payment->amount);
        
        $totalPayments = $debt->payments()->sum('amount');
        $this->assertEquals(1000, $totalPayments);
        
        $this->assertEquals('lunas', $invoice->status);
        $this->assertEquals(1000, (float) $invoice->down_payment);
        
        $debt->refresh();
        $this->assertEquals(Debt::STATUS_LUNAS, $debt->status);

        $transaction = Transaction::where('description', $invoice->transactionDescription())->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(1000, (float) $transaction->amount);
    }
}

