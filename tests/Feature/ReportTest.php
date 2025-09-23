<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Exports\FinancialReportExport;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_report_shows_all_transactions()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $category = Category::factory()->create();
        $now = now();

        $firstTransaction = Transaction::factory()->create([
            'category_id' => $category->id,
            'date' => $now->toDateString(),
        ]);

        $secondTransaction = Transaction::factory()->create([
            'category_id' => $category->id,
            'date' => $now->toDateString(),
        ]);

        $excludedTransaction = Transaction::factory()->create([
            'category_id' => $category->id,
            'date' => $now->copy()->subMonth()->toDateString(),
        ]);

        $start = $now->copy()->startOfMonth()->toDateString();
        $end = $now->copy()->endOfMonth()->toDateString();

        $response = $this->actingAs($admin)->get("/reports?start_date={$start}&end_date={$end}");

        $response->assertStatus(200);
        $response->assertViewHas('transactions', function ($transactions) use ($firstTransaction, $secondTransaction, $excludedTransaction) {
            return $transactions->contains('id', $firstTransaction->id)
                && $transactions->contains('id', $secondTransaction->id)
                && ! $transactions->contains('id', $excludedTransaction->id);
        });
    }

    public function test_admin_report_shows_all_debts()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $now = now();

        $firstDebt = Debt::factory()->create([
            'due_date' => $now->toDateString(),
        ]);

        $secondDebt = Debt::factory()->create([
            'due_date' => $now->toDateString(),
        ]);

        $excludedDebt = Debt::factory()->create([
            'due_date' => $now->copy()->addMonth()->toDateString(),
        ]);

        $start = $now->copy()->startOfMonth()->toDateString();
        $end = $now->copy()->endOfMonth()->toDateString();

        $response = $this->actingAs($admin)->get("/reports?start_date={$start}&end_date={$end}");

        $response->assertStatus(200);
        $response->assertViewHas('debts', function ($debts) use ($firstDebt, $secondDebt, $excludedDebt) {
            return $debts->contains('id', $firstDebt->id)
                && $debts->contains('id', $secondDebt->id)
                && ! $debts->contains('id', $excludedDebt->id);
        });
    }

    public function test_export_generates_file_with_all_data_for_admin()
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $category = Category::factory()->create();
        $now = now();

        $firstTransaction = Transaction::factory()->create([
            'category_id' => $category->id,
            'date' => $now->toDateString(),
        ]);

        $secondTransaction = Transaction::factory()->create([
            'category_id' => $category->id,
            'date' => $now->toDateString(),
        ]);

        $excludedTransaction = Transaction::factory()->create([
            'category_id' => $category->id,
            'date' => $now->copy()->subMonth()->toDateString(),
        ]);

        $firstDebt = Debt::factory()->create([
            'due_date' => $now->toDateString(),
        ]);

        $secondDebt = Debt::factory()->create([
            'due_date' => $now->toDateString(),
        ]);

        $excludedDebt = Debt::factory()->create([
            'due_date' => $now->copy()->addMonth()->toDateString(),
        ]);

        Excel::fake();

        $start = $now->copy()->startOfMonth()->toDateString();
        $end = $now->copy()->endOfMonth()->toDateString();

        $response = $this->actingAs($admin)->get("/reports/export?start_date={$start}&end_date={$end}&format=xlsx");

        $response->assertStatus(200);

        $fileName = "Laporan_Keuangan_{$start}_sampai_{$end}.xlsx";
        Excel::assertDownloaded($fileName, function (FinancialReportExport $export) use ($firstTransaction, $secondTransaction, $excludedTransaction, $firstDebt, $secondDebt, $excludedDebt) {
            $view = $export->view();
            $viewData = $view->getData();

            $transactions = $viewData['transactions'];
            $debts = $viewData['debts'];

            return $transactions->contains('id', $firstTransaction->id)
                && $transactions->contains('id', $secondTransaction->id)
                && ! $transactions->contains('id', $excludedTransaction->id)
                && $debts->contains('id', $firstDebt->id)
                && $debts->contains('id', $secondDebt->id)
                && ! $debts->contains('id', $excludedDebt->id);
        });
    }

    public function test_staff_cannot_access_reports()
    {
        $staff = User::factory()->create(['role' => Role::STAFF]);
        $now = now();

        $start = $now->copy()->startOfMonth()->toDateString();
        $end = $now->copy()->endOfMonth()->toDateString();

        $response = $this->actingAs($staff)->get("/reports?start_date={$start}&end_date={$end}");

        $response->assertStatus(403);
    }
}
