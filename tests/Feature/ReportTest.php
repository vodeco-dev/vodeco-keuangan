<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

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
