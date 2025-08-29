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
        $monthly_trends = Transaction::query()
            ->select(
                DB::raw("strftime('%Y', date) as year, strftime('%m', date) as month"),
                DB::raw('SUM(CASE WHEN categories.type = "pemasukan" THEN amount ELSE 0 END) as pemasukan'),
                DB::raw('SUM(CASE WHEN categories.type = "pengeluaran" THEN amount ELSE 0 END) as pengeluaran')
            )
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $request->user()->id) // Keamanan: Pastikan tren juga milik user
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        $max_value = 0;
        if ($monthly_trends->isNotEmpty()) {
            $max_pemasukan = $monthly_trends->max('pemasukan');
            $max_pengeluaran = $monthly_trends->max('pengeluaran');
            $max_value = max($max_pemasukan, $max_pengeluaran);
        }

        $recent_transactions = Transaction::with('category')
            ->where('user_id', $request->user()->id) // Keamanan: Pastikan transaksi terbaru milik user
            ->orderBy('date', 'desc')
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
