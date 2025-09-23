<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TransactionService();
        $this->user = User::factory()->create();
    }

    public function test_get_transactions_for_user_can_be_filtered()
    {
        $incomeCategory = Category::factory()->create(['type' => 'pemasukan']);
        $expenseCategory = Category::factory()->create(['type' => 'pengeluaran']);

        $transaction1 = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'description' => 'Gaji Bulanan',
            'date' => '2023-01-01',
            'category_id' => $incomeCategory->id,
        ]);
        $transaction2 = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'description' => 'Beli Kopi',
            'date' => '2023-01-02',
            'category_id' => $expenseCategory->id,
        ]);
        $transaction3 = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'description' => 'Gaji Bonus',
            'date' => '2023-01-03',
            'category_id' => $incomeCategory->id,
        ]);

        // Filter by search
        $request = new Request(['search' => 'Gaji']);
        $transactions = $this->service->getTransactionsForUser($this->user, $request);
        $this->assertCount(2, $transactions);
        $this->assertTrue($transactions->contains($transaction1));
        $this->assertTrue($transactions->contains($transaction3));

        // Filter by date
        $request = new Request(['date' => '2023-01-02']);
        $transactions = $this->service->getTransactionsForUser($this->user, $request);
        $this->assertCount(1, $transactions);
        $this->assertTrue($transactions->contains($transaction2));

        // Filter by category
        $request = new Request(['category_id' => $expenseCategory->id]);
        $transactions = $this->service->getTransactionsForUser($this->user, $request);
        $this->assertCount(1, $transactions);
        $this->assertTrue($transactions->contains($transaction2));

        // Filter by type
        $request = new Request(['type' => 'pemasukan']);
        $transactions = $this->service->getTransactionsForUser($this->user, $request);
        $this->assertCount(2, $transactions);
        $this->assertTrue($transactions->contains($transaction1));
        $this->assertTrue($transactions->contains($transaction3));
    }

    public function test_clear_summary_cache_for_user()
    {
        $cacheKey = 'transaction_summary_for_user_' . $this->user->id;
        \Illuminate\Support\Facades\Cache::put($cacheKey, 'test_value', 60);

        $this->service->clearSummaryCacheForUser($this->user);

        $this->assertFalse(\Illuminate\Support\Facades\Cache::has($cacheKey));
    }

    public function test_get_all_transactions_can_be_filtered()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $incomeCategory = Category::factory()->create(['type' => 'pemasukan']);
        $expenseCategory = Category::factory()->create(['type' => 'pengeluaran']);

        $transaction1 = Transaction::factory()->create([
            'user_id' => $user1->id,
            'description' => 'Gaji Bulanan',
            'date' => '2023-01-01',
            'category_id' => $incomeCategory->id,
        ]);
        $transaction2 = Transaction::factory()->create([
            'user_id' => $user2->id,
            'description' => 'Beli Kopi',
            'date' => '2023-01-02',
            'category_id' => $expenseCategory->id,
        ]);

        $transaction3 = Transaction::factory()->create([
            'user_id' => $user1->id,
            'description' => 'Langganan Software',
            'date' => '2023-02-10',
            'category_id' => $expenseCategory->id,
        ]);

        $request = new Request(['search' => 'Gaji']);
        $transactions = $this->service->getAllTransactions($request);
        $this->assertCount(1, $transactions);
        $this->assertTrue($transactions->contains($transaction1));

        $request = new Request(['month' => 1, 'year' => 2023]);
        $transactions = $this->service->getAllTransactions($request);
        $this->assertTrue($transactions->contains($transaction1));
        $this->assertTrue($transactions->contains($transaction2));
        $this->assertFalse($transactions->contains($transaction3));

        $request = new Request(['start_date' => '2023-01-01', 'end_date' => '2023-01-31']);
        $transactions = $this->service->getAllTransactions($request);
        $this->assertTrue($transactions->contains($transaction1));
        $this->assertTrue($transactions->contains($transaction2));
        $this->assertFalse($transactions->contains($transaction3));
    }

    public function test_get_all_summary()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $incomeCategory = Category::factory()->create(['type' => 'pemasukan']);
        $expenseCategory = Category::factory()->create(['type' => 'pengeluaran']);

        Transaction::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $incomeCategory->id,
            'amount' => 1000,
            'date' => '2023-01-05',
        ]);
        Transaction::factory()->create([
            'user_id' => $user2->id,
            'category_id' => $expenseCategory->id,
            'amount' => 300,
            'date' => '2023-02-10',
        ]);

        $summary = $this->service->getAllSummary();

        $this->assertEquals(1000, $summary['totalPemasukan']);
        $this->assertEquals(300, $summary['totalPengeluaran']);
        $this->assertEquals(700, $summary['saldo']);

        $request = new Request(['month' => 1, 'year' => 2023]);
        $januarySummary = $this->service->getAllSummary($request);

        $this->assertEquals(1000, $januarySummary['totalPemasukan']);
        $this->assertEquals(0, $januarySummary['totalPengeluaran']);
        $this->assertEquals(1000, $januarySummary['saldo']);
    }

    public function test_get_available_months()
    {
        $user = User::factory()->create();
        $incomeCategory = Category::factory()->create(['type' => 'pemasukan']);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'date' => '2023-01-01',
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'date' => '2023-03-01',
        ]);

        $months = $this->service->getAvailableMonths();

        $this->assertGreaterThanOrEqual(2, $months->count());
        $this->assertTrue($months->contains(function ($month) {
            return $month['month'] === 3 && $month['year'] === 2023;
        }));
    }

    public function test_clear_all_summary_cache()
    {
        $cacheKey = 'transaction_summary_for_all';
        \Illuminate\Support\Facades\Cache::put($cacheKey, 'test_value', 60);

        $this->service->clearAllSummaryCache();

        $this->assertFalse(\Illuminate\Support\Facades\Cache::has($cacheKey));
    }

    public function test_prepare_chart_data()
    {
        $incomeCategory = Category::factory()->create(['type' => 'pemasukan']);
        $expenseCategory = Category::factory()->create(['type' => 'pengeluaran']);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $incomeCategory->id,
            'amount' => 1000,
            'date' => '2023-01-01',
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 500,
            'date' => '2023-01-02',
        ]);

        $chartData = $this->service->prepareChartData($this->user, '2023-01-01', '2023-01-03');

        $this->assertEquals(['01 Jan', '02 Jan', '03 Jan'], $chartData['labels']->toArray());
        $this->assertEquals([1000, 0, 0], $chartData['pemasukan']->toArray());
        $this->assertEquals([0, 500, 0], $chartData['pengeluaran']->toArray());
    }

    public function test_prepare_chart_data_with_no_data()
    {
        $chartData = $this->service->prepareChartData($this->user, '2023-01-01', '2023-01-03');

        $this->assertEquals(['01 Jan', '02 Jan', '03 Jan'], $chartData['labels']->toArray());
        $this->assertEquals([0, 0, 0], $chartData['pemasukan']->toArray());
        $this->assertEquals([0, 0, 0], $chartData['pengeluaran']->toArray());
    }

    public function test_prepare_chart_data_with_filters()
    {
        $incomeCategory = Category::factory()->create(['type' => 'pemasukan']);
        $expenseCategory = Category::factory()->create(['type' => 'pengeluaran']);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $incomeCategory->id,
            'amount' => 1000,
            'date' => '2023-01-01',
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 500,
            'date' => '2023-01-02',
        ]);

        $incomeOnly = $this->service->prepareChartData($this->user, '2023-01-01', '2023-01-03', null, 'pemasukan');
        $this->assertEquals([1000, 0, 0], $incomeOnly['pemasukan']->toArray());
        $this->assertEquals([0, 0, 0], $incomeOnly['pengeluaran']->toArray());

        $expenseOnly = $this->service->prepareChartData($this->user, '2023-01-01', '2023-01-03', null, 'pengeluaran');
        $this->assertEquals([0, 0, 0], $expenseOnly['pemasukan']->toArray());
        $this->assertEquals([0, 500, 0], $expenseOnly['pengeluaran']->toArray());

        $categoryFiltered = $this->service->prepareChartData($this->user, '2023-01-01', '2023-01-03', $expenseCategory->id);
        $this->assertEquals([0, 0, 0], $categoryFiltered['pemasukan']->toArray());
        $this->assertEquals([0, 500, 0], $categoryFiltered['pengeluaran']->toArray());
    }
}
