<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Debt;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private FinancialReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FinancialReportService();
    }

    public function test_generate_returns_transactions_and_totals(): void
    {
        $user = User::factory()->create();
        $incomeCategory = Category::factory()->create(['type' => 'pemasukan']);
        $expenseCategory = Category::factory()->create(['type' => 'pengeluaran']);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'amount' => 1000,
            'date' => now(),
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 500,
            'date' => now(),
        ]);

        $result = $this->service->generate(
            $user->id,
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString()
        );

        $this->assertCount(2, $result['transactions']);
        $this->assertCount(1, $result['incomeTransactions']);
        $this->assertCount(1, $result['expenseTransactions']);
        $this->assertEquals(1000, $result['totals']['pemasukan']);
        $this->assertEquals(500, $result['totals']['pengeluaran']);
        $this->assertEquals(500, $result['totals']['selisih']);
    }

    public function test_generate_filters_by_category(): void
    {
        $user = User::factory()->create();
        $category1 = Category::factory()->create(['type' => 'pemasukan']);
        $category2 = Category::factory()->create(['type' => 'pemasukan']);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'amount' => 1000,
            'date' => now(),
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'amount' => 500,
            'date' => now(),
        ]);

        $result = $this->service->generate(
            $user->id,
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString(),
            $category1->id
        );

        $this->assertCount(1, $result['transactions']);
        $this->assertEquals(1000, $result['totals']['pemasukan']);
    }

    public function test_generate_filters_by_type(): void
    {
        $user = User::factory()->create();
        $incomeCategory = Category::factory()->create(['type' => 'pemasukan']);
        $expenseCategory = Category::factory()->create(['type' => 'pengeluaran']);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'amount' => 1000,
            'date' => now(),
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 500,
            'date' => now(),
        ]);

        $result = $this->service->generate(
            $user->id,
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString(),
            null,
            'pemasukan'
        );

        $this->assertCount(1, $result['transactions']);
        $this->assertCount(1, $result['incomeTransactions']);
        $this->assertCount(0, $result['expenseTransactions']);
    }

        public function test_generate_includes_debts(): void
    {
        $user = User::factory()->create();
        $debt = Debt::factory()->create([
            'user_id' => $user->id,
            'amount' => 1000,
            'due_date' => now(),
        ]);

        Payment::factory()->create([
            'debt_id' => $debt->id,
            'amount' => 300,
            'payment_date' => now(),
        ]);

        $result = $this->service->generate(
            $user->id,
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString()
        );

        $this->assertCount(1, $result['debts']);
        $this->assertEquals(1000, $result['totals']['hutang']);
        $this->assertEquals(300, $result['totals']['pembayaranHutang']);
        $this->assertEquals(700, $result['totals']['sisaHutang']);
    }

    public function test_generate_filters_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $category = Category::factory()->create(['type' => 'pemasukan']);

        Transaction::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'amount' => 1000,
            'date' => now(),
        ]);

        Transaction::factory()->create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'amount' => 500,
            'date' => now(),
        ]);

        $result = $this->service->generate(
            $user1->id,
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString()
        );

        $this->assertCount(1, $result['transactions']);
        $this->assertEquals(1000, $result['totals']['pemasukan']);
    }
}

