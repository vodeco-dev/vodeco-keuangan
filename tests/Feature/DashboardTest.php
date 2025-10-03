<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_summary_and_financial_overview(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-05-15 12:00:00'));

        $summary = [
            'totalPemasukan' => 300000,
            'totalPengeluaran' => 120000,
            'saldo' => 180000,
        ];

        $this->app->bind(TransactionService::class, function () use ($summary) {
            return new class($summary) extends TransactionService {
                public function __construct(private array $summary)
                {
                }

                public function getAllSummary(?Request $request = null): array
                {
                    return $this->summary;
                }
            };
        });

        $user = User::factory()->create();

        $incomeCategory = Category::factory()->create(['type' => 'pemasukan']);
        $expenseCategory = Category::factory()->create(['type' => 'pengeluaran']);

        Transaction::factory()->create([
            'category_id' => $incomeCategory->id,
            'user_id' => $user->id,
            'amount' => 150000,
            'date' => Carbon::now()->startOfMonth()->addDays(2),
        ]);

        Transaction::factory()->create([
            'category_id' => $expenseCategory->id,
            'user_id' => $user->id,
            'amount' => 50000,
            'date' => Carbon::now()->startOfMonth()->addDays(4),
        ]);

        Transaction::factory()->create([
            'category_id' => $incomeCategory->id,
            'user_id' => $user->id,
            'amount' => 100000,
            'date' => Carbon::now()->subMonth()->startOfMonth()->addDays(3),
        ]);

        Transaction::factory()->create([
            'category_id' => $expenseCategory->id,
            'user_id' => $user->id,
            'amount' => 20000,
            'date' => Carbon::now()->subMonth()->startOfMonth()->addDays(5),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard');
        $response->assertViewHas('summary', $summary);
        $response->assertViewHas('financial_overview', function (array $overview) {
            $this->assertSame(150000, $overview['pemasukan']);
            $this->assertSame(50000, $overview['pengeluaran']);
            $this->assertSame(100000, $overview['net']);
            $this->assertEqualsWithDelta(25.0, $overview['percent_change'], 0.001);

            return true;
        });
        $response->assertViewHas('selected_month', '2024-05');
        $response->assertViewHas('recent_transactions', function ($transactions) {
            $this->assertCount(4, $transactions);

            return $transactions->first()->date->greaterThanOrEqualTo($transactions->last()->date);
        });

        Carbon::setTestNow();
    }
}
