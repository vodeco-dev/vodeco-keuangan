<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Debt;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceSettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_settlement_form_requires_valid_token(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => 'belum bayar',
            'settlement_token_expires_at' => now()->addDay(),
        ]);

        $response = $this->get(route('invoices.settlement.show', ['token' => $invoice->settlement_token]));

        $response->assertStatus(200);
        $response->assertSee($invoice->number);
    }

    public function test_public_settlement_marks_invoice_as_paid_and_logs_activity(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['type' => 'pemasukan']);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'total' => 500000,
            'status' => 'belum bayar',
            'down_payment' => 0,
            'settlement_token_expires_at' => now()->addDay(),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'category_id' => $category->id,
            'description' => 'Jasa',
            'quantity' => 1,
            'price' => 500000,
        ]);

        $response = $this->post(route('invoices.settlement.store', ['token' => $invoice->settlement_token]));

        $response->assertStatus(200);
        $response->assertSee('Pelunasan Berhasil', false);

        $invoice->refresh();

        $this->assertSame('lunas', $invoice->status);
        $this->assertNull($invoice->settlement_token);
        $this->assertNull($invoice->settlement_token_expires_at);
        $this->assertEquals($invoice->total, (float) $invoice->down_payment);

        $this->assertDatabaseHas('activity_logs', [
            'description' => 'Konfirmasi pelunasan invoice #' . $invoice->number . ' melalui tautan publik',
        ]);

        $debt = Debt::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($debt);
        $this->assertSame(Debt::STATUS_LUNAS, $debt->status);
        $this->assertEquals($invoice->total, (float) $debt->payments()->sum('amount'));

        $transaction = Transaction::where('description', 'Pelunasan invoice #' . $invoice->number . ' melalui tautan konfirmasi')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($invoice->user_id, $transaction->user_id);
        $this->assertEquals($invoice->total, (float) $transaction->amount);
    }

    public function test_settlement_token_rotation_and_revocation(): void
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'settlement_token_expires_at' => now()->addDay(),
        ]);

        $originalToken = $invoice->settlement_token;

        $expiresAt = now()->addDays(3);

        $this->actingAs($user)->post(route('invoices.settlement-token.refresh', $invoice), [
            'expires_at' => $expiresAt->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $invoice->refresh();

        $this->assertNotSame($originalToken, $invoice->settlement_token);
        $this->assertEquals($expiresAt->format('Y-m-d H:i'), $invoice->settlement_token_expires_at->format('Y-m-d H:i'));

        $this->actingAs($user)->delete(route('invoices.settlement-token.revoke', $invoice))->assertRedirect();

        $invoice->refresh();

        $this->assertNull($invoice->settlement_token);
        $this->assertNull($invoice->settlement_token_expires_at);
    }

    public function test_bruteforce_attempts_are_rate_limited(): void
    {
        $token = Str::random(64);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('invoices.settlement.store', ['token' => $token]))->assertStatus(404);
        }

        $this->post(route('invoices.settlement.store', ['token' => $token]))->assertStatus(429);
    }
}
