<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Debt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_pass_through_debt(): void
    {
        Carbon::setTestNow('2024-01-01');

        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Operasional',
            'type' => 'pengeluaran',
        ]);

        $response = $this->actingAs($user)->post('/debts', [
            'description' => 'Invoices Iklan Expense',
            'related_party' => 'Budi',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'due_date' => now()->addMonth()->toDateString(),
            'category_id' => $category->id,
        ]);

        $response->assertRedirect('/debts');

        $this->assertDatabaseHas('debts', [
            'description' => 'Invoices Iklan Expense',
            'related_party' => 'Budi',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'status' => Debt::STATUS_BELUM_LUNAS,
            'category_id' => $category->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_user_can_record_payment_and_mark_down_payment_paid(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::create([
            'name' => 'Pembayaran Hutang',
            'type' => 'pengeluaran',
        ]);

        $debt = Debt::create([
            'user_id' => $user->id,
            'description' => 'Test Debt',
            'related_party' => 'Budi',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'status' => Debt::STATUS_BELUM_LUNAS,
            'due_date' => now()->addWeek(),
            'category_id' => $category->id,
        ]);

        $response = $this->post(route('debts.pay', $debt), [
            'payment_amount' => 1000,
            'category_id' => $category->id,
        ]);

        $response->assertRedirect(route('debts.index'));
        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'status' => Debt::STATUS_LUNAS,
        ]);
        
        // Verify transaction was created for the payment
        $this->assertDatabaseHas('transactions', [
            'amount' => 1000,
            'user_id' => $user->id,
        ]);
        
        // Verify payment is linked to transaction
        $this->assertDatabaseHas('payments', [
            'debt_id' => $debt->id,
            'amount' => 1000,
        ]);
    }

    public function test_debts_index_is_paginated(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 20; $i++) {
            Debt::create([
                'user_id' => $user->id,
                'description' => 'Debt ' . $i,
                'related_party' => 'Budi',
                'type' => Debt::TYPE_PASS_THROUGH,
                'amount' => 1000,
                'status' => Debt::STATUS_BELUM_LUNAS,
            ]);
        }

        $response = $this->actingAs($user)->get('/debts');

        $response->assertViewHas('debts', function ($debts) {
            return $debts->perPage() == 15 && $debts->total() == 20;
        });

        $responsePage2 = $this->actingAs($user)->get('/debts?page=2');
        $responsePage2->assertViewHas('debts', function ($debts) {
            return $debts->currentPage() == 2 && $debts->count() == 5;
        });
    }

    public function test_due_date_defaults_to_two_months_from_now_when_not_provided(): void
    {
        Carbon::setTestNow('2024-02-15');

        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Operasional',
            'type' => 'pengeluaran',
        ]);

        $this->actingAs($user)->post('/debts', [
            'description' => 'Default Due Date Debt',
            'related_party' => 'Ani',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 5000,
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('debts', [
            'description' => 'Default Due Date Debt',
            'due_date' => Carbon::now()->addMonths(2)->toDateString(),
        ]);

        Carbon::setTestNow();
    }

    public function test_overdue_debt_is_marked_as_failed_and_transaction_created(): void
    {
        Carbon::setTestNow('2024-03-01');

        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Operasional',
            'type' => 'pengeluaran',
        ]);

        $debt = Debt::create([
            'user_id' => $user->id,
            'description' => 'Overdue Debt',
            'related_party' => 'Andi',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1500,
            'status' => Debt::STATUS_BELUM_LUNAS,
            'due_date' => Carbon::now()->subDay(),
            'category_id' => $category->id,
        ]);

        $this->actingAs($user)->get('/debts');

        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'status' => Debt::STATUS_GAGAL,
        ]);

        $this->assertDatabaseHas('transactions', [
            'description' => 'Iklan Gagal: Overdue Debt',
            'amount' => 1500,
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_pass_through_payment_creates_expense_transaction(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $expenseCategory = Category::create([
            'name' => 'Iklan',
            'type' => 'pengeluaran',
        ]);

        $debt = Debt::create([
            'user_id' => $user->id,
            'description' => 'Invoices Iklan untuk Client A',
            'related_party' => 'Client A',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 5000,
            'status' => Debt::STATUS_BELUM_LUNAS,
            'due_date' => now()->addWeek(),
            'category_id' => $expenseCategory->id,
        ]);

        $response = $this->post(route('debts.pay', $debt), [
            'payment_amount' => 5000,
            'category_id' => $expenseCategory->id,
        ]);

        $response->assertRedirect(route('debts.index'));
        
        // Verify debt is marked as paid
        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'status' => Debt::STATUS_LUNAS,
        ]);

        // Verify transaction was created as expense (pengeluaran)
        $this->assertDatabaseHas('transactions', [
            'amount' => 5000,
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
        ]);

        // Verify payment is linked to transaction
        $payment = $debt->payments()->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->transaction_id);
        $this->assertEquals(5000, $payment->amount);
    }

    public function test_down_payment_creates_income_transaction(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $incomeCategory = Category::create([
            'name' => 'Down Payment',
            'type' => 'pemasukan',
        ]);

        $debt = Debt::create([
            'user_id' => $user->id,
            'description' => 'Down Payment dari Client B',
            'related_party' => 'Client B',
            'type' => Debt::TYPE_DOWN_PAYMENT,
            'amount' => 3000,
            'status' => Debt::STATUS_BELUM_LUNAS,
            'due_date' => now()->addWeek(),
            'category_id' => $incomeCategory->id,
        ]);

        $response = $this->post(route('debts.pay', $debt), [
            'payment_amount' => 3000,
            'category_id' => $incomeCategory->id,
        ]);

        $response->assertRedirect(route('debts.index'));
        
        // Verify debt is marked as paid
        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'status' => Debt::STATUS_LUNAS,
        ]);

        // Verify transaction was created as income (pemasukan)
        $this->assertDatabaseHas('transactions', [
            'amount' => 3000,
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
        ]);

        // Verify payment is linked to transaction
        $payment = $debt->payments()->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->transaction_id);
        $this->assertEquals(3000, $payment->amount);
    }

    public function test_partial_payment_creates_transaction_for_each_payment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $incomeCategory = Category::create([
            'name' => 'Down Payment',
            'type' => 'pemasukan',
        ]);

        $debt = Debt::create([
            'user_id' => $user->id,
            'description' => 'Down Payment Partial',
            'related_party' => 'Client C',
            'type' => Debt::TYPE_DOWN_PAYMENT,
            'amount' => 10000,
            'status' => Debt::STATUS_BELUM_LUNAS,
            'due_date' => now()->addWeek(),
            'category_id' => $incomeCategory->id,
        ]);

        // First payment
        $this->post(route('debts.pay', $debt), [
            'payment_amount' => 4000,
            'category_id' => $incomeCategory->id,
        ]);

        // Verify first transaction
        $this->assertDatabaseHas('transactions', [
            'amount' => 4000,
            'user_id' => $user->id,
        ]);

        // Second payment
        $this->post(route('debts.pay', $debt), [
            'payment_amount' => 6000,
            'category_id' => $incomeCategory->id,
        ]);

        // Verify second transaction
        $this->assertDatabaseHas('transactions', [
            'amount' => 6000,
            'user_id' => $user->id,
        ]);

        // Verify debt is now fully paid
        $debt->refresh();
        $this->assertEquals(Debt::STATUS_LUNAS, $debt->status);
        $this->assertEquals(2, $debt->payments()->count());
        
        // Verify both payments are linked to transactions
        foreach ($debt->payments as $payment) {
            $this->assertNotNull($payment->transaction_id);
        }
    }
}
