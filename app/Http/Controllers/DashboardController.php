<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Transaction;
use App\Services\TransactionService;
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
        $user = $request->user();
        $isAdminOrAccountant = in_array($user->role, [Role::ADMIN, Role::ACCOUNTANT]);

        // Filter dan ringkasan keadaan keuangan per bulan
        $selectedMonth = $request->input('month');
        if ($selectedMonth) {
            // Validasi format input (YYYY-MM)
            if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
                $selectedMonth = now()->format('Y-m');
            }
            // Format input dari <input type="month"> adalah YYYY-MM
            [$year, $month] = explode('-', $selectedMonth);
            $year = (int) $year;
            $month = (int) $month;
            
            // Validasi range tahun dan bulan
            if ($year < 1900 || $year > 2100 || $month < 1 || $month > 12) {
                $year = now()->year;
                $month = now()->month;
                $selectedMonth = now()->format('Y-m');
            }
        } else {
            $year  = now()->year;
            $month = now()->month;
            $selectedMonth = now()->format('Y-m');
        }

        // Untuk summary, gunakan getAllSummary jika admin/accountant, atau buat query khusus untuk user biasa
        if ($isAdminOrAccountant) {
            $summaryRequest = new Request([
                'month' => $month,
                'year' => $year,
            ]);
            $summary = $this->transactionService->getAllSummary($summaryRequest);
        } else {
            // Untuk user biasa, hitung summary berdasarkan transaksi mereka di bulan yang dipilih
            $userSummaryQuery = Transaction::query()
                ->join('categories', 'transactions.category_id', '=', 'categories.id')
                ->where('transactions.user_id', $user->id)
                ->whereYear('transactions.date', $year)
                ->whereMonth('transactions.date', $month);
            
            $userSummary = $userSummaryQuery
                ->selectRaw("SUM(CASE WHEN categories.type = 'pemasukan' THEN transactions.amount ELSE 0 END) AS totalPemasukan")
                ->selectRaw("SUM(CASE WHEN categories.type = 'pengeluaran' THEN transactions.amount ELSE 0 END) AS totalPengeluaran")
                ->first();
            
            $pemasukan = $userSummary->totalPemasukan ?? 0;
            $pengeluaran = $userSummary->totalPengeluaran ?? 0;
            
            $summary = [
                'totalPemasukan' => $pemasukan,
                'totalPengeluaran' => $pengeluaran,
                'saldo' => $pemasukan - $pengeluaran,
            ];
        }

        // Total pemasukan & pengeluaran bulan terpilih
        $currentTotalsQuery = Transaction::query()
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereYear('transactions.date', $year)
            ->whereMonth('transactions.date', $month);

        // Filter berdasarkan user jika bukan admin/accountant
        if (!$isAdminOrAccountant) {
            $currentTotalsQuery->where('transactions.user_id', $user->id);
        }

        $currentTotals = $currentTotalsQuery
            ->selectRaw("SUM(CASE WHEN categories.type = 'pemasukan' THEN transactions.amount ELSE 0 END) as pemasukan")
            ->selectRaw("SUM(CASE WHEN categories.type = 'pengeluaran' THEN transactions.amount ELSE 0 END) as pengeluaran")
            ->first();

        $currentNet = ($currentTotals->pemasukan ?? 0) - ($currentTotals->pengeluaran ?? 0);

        // Hitung data bulan sebelumnya untuk perbandingan
        $previous = Carbon::create($year, $month)->subMonth();
        $previousTotalsQuery = Transaction::query()
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereYear('transactions.date', $previous->year)
            ->whereMonth('transactions.date', $previous->month);

        // Filter berdasarkan user jika bukan admin/accountant
        if (!$isAdminOrAccountant) {
            $previousTotalsQuery->where('transactions.user_id', $user->id);
        }

        $previousTotals = $previousTotalsQuery
            ->selectRaw("SUM(CASE WHEN categories.type = 'pemasukan' THEN transactions.amount ELSE 0 END) as pemasukan")
            ->selectRaw("SUM(CASE WHEN categories.type = 'pengeluaran' THEN transactions.amount ELSE 0 END) as pengeluaran")
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

        // Transaksi terbaru dengan filter bulan yang dipilih
        $recentTransactionsQuery = Transaction::with(['category', 'user'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter berdasarkan user jika bukan admin/accountant
        if (!$isAdminOrAccountant) {
            $recentTransactionsQuery->where('user_id', $user->id);
        }

        $recent_transactions = $recentTransactionsQuery->take(5)->get();

        // Siapkan data chart untuk bulan yang dipilih
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        
        $chartData = $this->transactionService->prepareChartData(
            $isAdminOrAccountant ? null : $user,
            $startDate->toDateString(),
            $endDate->toDateString()
        );

        return view('dashboard', [
            'title'               => 'Dashboard',
            'summary'             => $summary,
            'financial_overview'  => $financialOverview,
            'selected_month'      => $selectedMonth,
            'recent_transactions' => $recent_transactions,
            'show_user_column'    => $isAdminOrAccountant,
            'chartData'           => $chartData,
        ]);
    }
}
