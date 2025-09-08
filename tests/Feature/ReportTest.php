<?php

namespace Tests\Feature;

use App\Exports\TransactionsExport;
use App\Models\Category;
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

    public function test_export_generates_file_filtered_by_user_and_date()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();

        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'date' => now()->toDateString(),
        ]);

        // Outside date range
        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'date' => now()->subMonth()->toDateString(),
        ]);

        // Belongs to another user
        Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'date' => now()->toDateString(),
        ]);

        Excel::fake();

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $response = $this->actingAs($user)->get("/reports/export?start_date={$start}&end_date={$end}&format=xlsx");

        $response->assertStatus(200);

        $fileName = "Laporan_Keuangan_{$start}_sampai_{$end}.xlsx";
        Excel::assertDownloaded($fileName, function (TransactionsExport $export) use ($user) {
            $collection = $export->collection();

            return $collection->count() === 1 &&
                $collection->first()->user_id === $user->id;
        });
    }

    public function test_user_can_export_report()
    {
        $user = User::factory()->create();
        Excel::fake();

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $response = $this->actingAs($user)->get("/reports/export?start_date={$start}&end_date={$end}&format=xlsx");

        $response->assertStatus(200);

        $fileName = "Laporan_Keuangan_{$start}_sampai_{$end}.xlsx";
        Excel::assertDownloaded($fileName);
    }
}
