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
        // Summary sekarang memfilter berdasarkan bulan yang dipilih (Mei 2024)
        // Hanya transaksi di bulan Mei yang dihitung: 150000 pemasukan, 50000 pengeluaran
        $response->assertViewHas('summary', [
            'totalPemasukan' => 150000,
            'totalPengeluaran' => 50000,
            'saldo' => 100000,
        ]);
        $response->assertViewHas('financial_overview', function (array $overview) {
            $this->assertSame(150000, $overview['pemasukan']);
            $this->assertSame(50000, $overview['pengeluaran']);
            $this->assertSame(100000, $overview['net']);
            $this->assertEqualsWithDelta(25.0, $overview['percent_change'], 0.001);

            return true;
        });
        $response->assertViewHas('selected_month', '2024-05');
        $response->assertViewHas('recent_transactions', function ($transactions) {
            // Hanya transaksi di bulan yang dipilih (Mei 2024) yang ditampilkan
            $this->assertCount(2, $transactions);
            
            // Pastikan semua transaksi berada di bulan Mei 2024
            foreach ($transactions as $transaction) {
                $this->assertEquals(2024, $transaction->date->year);
                $this->assertEquals(5, $transaction->date->month);
            }

            return $transactions->first()->date->greaterThanOrEqualTo($transactions->last()->date);
        });

        Carbon::setTestNow();
    }
}
