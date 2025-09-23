<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionService; // Tambahkan ini
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // Inject TransactionService
    public function __construct(private TransactionService $transactionService)
    {
    }

    public function index(Request $request)
    {
        $summary = $this->transactionService->getAllSummary($request);

        // Filter dan ringkasan keadaan keuangan per bulan
        $selectedMonth = $request->input('month');
        if ($selectedMonth) {
            // Format input dari <input type="month"> adalah YYYY-MM
            [$year, $month] = explode('-', $selectedMonth);
            $month = (int) $month;
        } else {
            $year  = now()->year;
            $month = now()->month;
            $selectedMonth = now()->format('Y-m');
        }

        // Total pemasukan & pengeluaran bulan terpilih
        $currentTotals = Transaction::query()
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->selectRaw("SUM(CASE WHEN categories.type = 'pemasukan' THEN amount ELSE 0 END) as pemasukan")
            ->selectRaw("SUM(CASE WHEN categories.type = 'pengeluaran' THEN amount ELSE 0 END) as pengeluaran")
            ->first();

        $currentNet = ($currentTotals->pemasukan ?? 0) - ($currentTotals->pengeluaran ?? 0);

        // Hitung data bulan sebelumnya untuk perbandingan
        $previous = Carbon::create($year, $month)->subMonth();
        $previousTotals = Transaction::query()
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereYear('date', $previous->year)
            ->whereMonth('date', $previous->month)
            ->selectRaw("SUM(CASE WHEN categories.type = 'pemasukan' THEN amount ELSE 0 END) as pemasukan")
            ->selectRaw("SUM(CASE WHEN categories.type = 'pengeluaran' THEN amount ELSE 0 END) as pengeluaran")
            ->first();

        $previousNet = ($previousTotals->pemasukan ?? 0) - ($previousTotals->pengeluaran ?? 0);

        $percentChange = null;
        if ($previousNet != 0) {
            $percentChange = (($currentNet - $previousNet) / abs($previousNet)) * 100;
        }

        $financialOverview = [
            'pemasukan'      => $currentTotals->pemasukan ?? 0,
            'pengeluaran'    => $currentTotals->pengeluaran ?? 0,
            'net'            => $currentNet,
            'percent_change' => $percentChange,
        ];

        $recent_transactions = Transaction::with(['category', 'user'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', [
            'title'               => 'Dashboard',
            'summary'             => $summary,
            'financial_overview'  => $financialOverview,
            'selected_month'      => $selectedMonth,
            'recent_transactions' => $recent_transactions,
            'show_user_column'    => true,
        ]);
    }
}
