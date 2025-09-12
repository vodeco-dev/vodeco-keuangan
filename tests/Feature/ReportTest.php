<?php

namespace Tests\Feature;

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

    public function test_report_shows_only_transactions_belonging_to_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();

        $userTransaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'date' => now()->toDateString(),
        ]);

        $otherTransaction = Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'date' => now()->toDateString(),
        ]);

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $response = $this->actingAs($user)->get("/reports?start_date={$start}&end_date={$end}");

        $response->assertStatus(200);
        $response->assertViewHas('transactions', function ($transactions) use ($userTransaction, $otherTransaction) {
            return $transactions->contains('id', $userTransaction->id)
                && ! $transactions->contains('id', $otherTransaction->id);
        });
    }

    public function test_report_shows_only_debts_belonging_to_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userDebt = Debt::factory()->create([
            'user_id' => $user->id,
            'due_date' => now()->toDateString(),
        ]);

        $otherDebt = Debt::factory()->create([
            'user_id' => $otherUser->id,
            'due_date' => now()->toDateString(),
        ]);

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $response = $this->actingAs($user)->get("/reports?start_date={$start}&end_date={$end}");

        $response->assertStatus(200);
        $response->assertViewHas('debts', function ($debts) use ($userDebt, $otherDebt) {
            return $debts->contains('id', $userDebt->id)
                && ! $debts->contains('id', $otherDebt->id);
        });
    }

    public function test_export_generates_file_with_correct_data()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'date' => now()->toDateString(),
        ]);

        $debt = Debt::factory()->create([
            'user_id' => $user->id,
            'due_date' => now()->toDateString(),
        ]);

        // Data that should not be in the export
        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'date' => now()->subMonth()->toDateString(),
        ]);
        Debt::factory()->create([
            'user_id' => $otherUser->id,
            'due_date' => now()->toDateString(),
        ]);

        Excel::fake();

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $response = $this->actingAs($user)->get("/reports/export?start_date={$start}&end_date={$end}&format=xlsx");

        $response->assertStatus(200);

        $fileName = "Laporan_Keuangan_{$start}_sampai_{$end}.xlsx";
        Excel::assertDownloaded($fileName, function (FinancialReportExport $export) use ($transaction, $debt) {
            $view = $export->view();
            $viewData = $view->getData();

            $transactions = $viewData['transactions'];
            $debts = $viewData['debts'];

            return $transactions->count() === 1 &&
                   $transactions->first()->id === $transaction->id &&
                   $debts->count() === 1 &&
                   $debts->first()->id === $debt->id;
        });
    }
}
