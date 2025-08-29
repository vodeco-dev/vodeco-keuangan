<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $summary = Transaction::getSummary();

        // ... (kode untuk $trendsQuery dan $monthly_trends tetap sama) ...
        $trendsQuery = Transaction::query()
            ->select(
                DB::raw("strftime('%Y', date) as year, strftime('%m', date) as month"),
                DB::raw('SUM(CASE WHEN categories.type = "pemasukan" THEN amount ELSE 0 END) as pemasukan'),
                DB::raw('SUM(CASE WHEN categories.type = "pengeluaran" THEN amount ELSE 0 END) as pengeluaran')
            )
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc');

        if ($request->filled('month')) {
            $year = substr($request->month, 0, 4);
            $month = substr($request->month, 5, 2);
            $trendsQuery->whereRaw("strftime('%Y', date) = ?", [$year])
                        ->whereRaw("strftime('%m', date) = ?", [$month]);
        }

        $monthly_trends = $trendsQuery->get();

        $max_value = 0;
        if ($monthly_trends->isNotEmpty()) {
            $max_pemasukan = $monthly_trends->max('pemasukan');
            $max_pengeluaran = $monthly_trends->max('pengeluaran');
            $max_value = max($max_pemasukan, $max_pengeluaran);
        }

        // (TAMBAHKAN INI) Query untuk mengambil 5 transaksi terbaru
        $recent_transactions = Transaction::with('category') // Eager load relasi category
            ->orderBy('date', 'desc') // Urutkan berdasarkan tanggal terbaru
            ->take(5) // Ambil 5 data
            ->get();

        return view('dashboard', [
            'title'               => 'Dashboard',
            'saldo'               => $summary['saldo'],
            'pemasukan'           => $summary['pemasukan'],
            'pengeluaran'         => $summary['pengeluaran'],
            'monthly_trends'      => $monthly_trends,
            'max_trend_value'     => $max_value,
            'recent_transactions' => $recent_transactions, // (TAMBAHKAN INI) Kirim data ke view
        ]);
    }
}