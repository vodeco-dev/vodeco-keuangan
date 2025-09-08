<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionService; // Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Inject TransactionService
    public function __construct(private TransactionService $transactionService)
    {
    }

    public function index(Request $request)
    {
        // Gunakan service untuk mendapatkan ringkasan yang aman dan efisien
        $summary = $this->transactionService->getSummaryForUser($request->user());

        // Query untuk tren bulanan (sudah aman)
        $selectedMonth = $request->input('month');

        if ($selectedMonth) {
            // Format input dari <input type="month"> adalah YYYY-MM
            [$year, $month] = explode('-', $selectedMonth);
        } else {
            $year  = now()->year;
            $month = null;
        }

        $driver     = DB::getDriverName();
        $dateSelect = $driver === 'sqlite'
            ? "CAST(strftime('%Y', date) AS INTEGER) AS year, CAST(strftime('%m', date) AS INTEGER) AS month"
            : "YEAR(date) AS year, MONTH(date) AS month";

        $monthly_trends_query = Transaction::query()
            ->selectRaw($dateSelect)
            ->selectRaw("SUM(CASE WHEN categories.type = 'pemasukan' THEN amount ELSE 0 END) as pemasukan")
            ->selectRaw("SUM(CASE WHEN categories.type = 'pengeluaran' THEN amount ELSE 0 END) as pengeluaran")
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $request->user()->id)
            ->whereYear('date', $year); // Keamanan: batasi berdasarkan tahun yang dipilih

        if ($month) {
            $monthly_trends_query->whereMonth('date', $month);
        }

        $monthly_trends = $monthly_trends_query
            ->groupBy('year', 'month')
            ->orderBy('month', 'asc')
            ->get();

        // Pastikan data tren berisi semua bulan agar grafik tampil jelas
        if (!$month) {
            $monthly_trends = collect(range(1, 12))->map(function ($m) use ($monthly_trends, $year) {
                $trend = $monthly_trends->firstWhere('month', $m);

                return (object) [
                    'year'        => (int) $year,
                    'month'       => $m,
                    'pemasukan'   => $trend->pemasukan ?? 0,
                    'pengeluaran' => $trend->pengeluaran ?? 0,
                ];
            });
        }

        $max_value = 0;
        if ($monthly_trends->isNotEmpty()) {
            $max_pemasukan = $monthly_trends->max('pemasukan');
            $max_pengeluaran = $monthly_trends->max('pengeluaran');
            $max_value = max($max_pemasukan, $max_pengeluaran);
        }

        $recent_transactions = Transaction::with('category')
            ->where('user_id', $request->user()->id) // Keamanan: Pastikan transaksi terbaru milik user
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', [
            'title'               => 'Dashboard',
            'saldo'               => $summary['saldo'],
            'pemasukan'           => $summary['totalPemasukan'],
            'pengeluaran'         => $summary['totalPengeluaran'],
            'monthly_trends'      => $monthly_trends,
            'max_trend_value'     => $max_value,
            'recent_transactions' => $recent_transactions,
        ]);
    }
}
