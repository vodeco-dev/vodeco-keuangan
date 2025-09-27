<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Exports\FinancialReportExport;
use App\Http\Requests\ReportRequest;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Transaction;
use App\Services\TransactionService;
use Carbon\Carbon;

use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index(ReportRequest $request)
    {
        $validated = $request->validated();
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];
        $currentUser = $request->user();
        $shouldFilterByUser = ! in_array($currentUser->role, [Role::ADMIN, Role::ACCOUNTANT]);

        $filters = [
            'category_id' => $validated['category_id'] ?? null,
            'type' => $validated['type'] ?? null,
            'period' => $validated['period'] ?? 'range',
            'date' => $request->input('date'),
            'month' => $request->input('month'),
            'year' => $request->input('year'),
        ];

        $filters['date'] = $filters['date'] ?? $startDate;
        $filters['month'] = $filters['month'] ?? Carbon::parse($startDate)->month;
        $filters['year'] = $filters['year'] ?? Carbon::parse($startDate)->year;

        // Ambil transaksi dalam rentang tanggal
        $transactionQuery = Transaction::with('category')
            ->when($shouldFilterByUser, function ($query) use ($currentUser) {
                $query->where('user_id', $currentUser->id);
            })
            ->whereBetween('date', [$startDate, $endDate]);

        if ($filters['category_id']) {
            $transactionQuery->where('category_id', $filters['category_id']);
        }

        if ($filters['type']) {
            $transactionQuery->whereHas('category', function ($query) use ($filters) {
                $query->where('type', $filters['type']);
            });
        }

        $transactions = $transactionQuery
            ->orderBy('date', 'desc')
            ->get();



        // Hitung total pemasukan dan pengeluaran
        $totalPemasukan = $transactions->where('category.type', 'pemasukan')->sum('amount');
        $totalPengeluaran = $transactions->where('category.type', 'pengeluaran')->sum('amount');
        $selisih = $totalPemasukan - $totalPengeluaran;

        // Ambil hutang dalam rentang tanggal
        $debts = Debt::with('payments')
            ->when($shouldFilterByUser, function ($query) use ($currentUser) {
                $query->where('user_id', $currentUser->id);
            })
            ->whereBetween('due_date', [$startDate, $endDate])
            ->orderBy('due_date', 'desc')
            ->get();

        // Hitung total hutang dan pembayaran
        $totalHutang = $debts->sum('amount');
        $totalPembayaranHutang = $debts->sum('paid_amount');
        $sisaHutang = $totalHutang - $totalPembayaranHutang;

        // Siapkan data untuk chart
        $chartData = $this->transactionService->prepareChartData(
            $shouldFilterByUser ? $currentUser : null,
            $startDate,
            $endDate,
            $filters['category_id'] ? (int) $filters['category_id'] : null,
            $filters['type']
        );

        $categories = Category::orderBy('name')->get();

        $exportQuery = array_filter(
            [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'category_id' => $filters['category_id'],
                'type' => $filters['type'],
                'period' => $filters['period'],
                'date' => $filters['date'],
                'month' => $filters['month'],
                'year' => $filters['year'],
            ],
            function ($value, $key) {
                if (in_array($key, ['start_date', 'end_date', 'period'])) {
                    return true;
                }

                return $value !== null && $value !== '';
            },
            ARRAY_FILTER_USE_BOTH
        );

        return view('reports.index', [
            'title' => 'Laporan',
            'transactions' => $transactions,
            'totalPemasukan' => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'selisih' => $selisih,
            'debts' => $debts,
            'totalHutang' => $totalHutang,
            'totalPembayaranHutang' => $totalPembayaranHutang,
            'sisaHutang' => $sisaHutang,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'chartData' => $chartData,
            'categories' => $categories,
            'filters' => $filters,
            'exportQuery' => $exportQuery,
        ]);
    }


    // Fungsi untuk handle ekspor
    public function export(ReportRequest $request)
    {
        $validated = $request->validated();
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];
        $format = $validated['format'] ?? 'xlsx'; // xlsx atau csv
        $currentUser = $request->user();
        $shouldFilterByUser = ! in_array($currentUser->role, [Role::ADMIN, Role::ACCOUNTANT]);

        $fileName = 'Laporan_Keuangan_'.$startDate.'_sampai_'.$endDate.'.'.$format;

        return Excel::download(
            new FinancialReportExport(
                $shouldFilterByUser ? $currentUser->id : null,
                $startDate,
                $endDate,
                isset($validated['category_id']) ? (int) $validated['category_id'] : null,
                $validated['type'] ?? null
            ),
            $fileName
        );
    }

}
