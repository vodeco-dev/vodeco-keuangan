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
            'description' => '[Otomatis] Gagal Project: Overdue Debt',
            'amount' => 1500,
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);

        Carbon::setTestNow();
    }
}
