<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_correct_summary_for_user(): void
    {
        $user = User::factory()->create();
        $incomeCategory = Category::factory()->create(['type' => 'pemasukan']);
        $expenseCategory = Category::factory()->create(['type' => 'pengeluaran']);

        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'amount' => 1000,
            'date' => now(),
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'amount' => 500,
            'date' => now(),
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 400,
            'date' => now(),
        ]);

        $service = new TransactionService();
        $summary = $service->getSummaryForUser($user);

        $this->assertEquals(1500, $summary['totalPemasukan']);
        $this->assertEquals(400, $summary['totalPengeluaran']);
        $this->assertEquals(1100, $summary['saldo']);
    }
}
