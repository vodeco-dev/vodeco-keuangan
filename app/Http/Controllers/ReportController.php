<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Exports\FinancialReportExport;
use App\Http\Requests\ReportRequest;
use App\Models\Category;
use App\Services\FinancialReportService;
use App\Services\TransactionService;
use Carbon\Carbon;

use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function __construct(
        private TransactionService $transactionService,
        private FinancialReportService $financialReportService
    ) {}

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

        $reportData = $this->financialReportService->generate(
            $shouldFilterByUser ? $currentUser->id : null,
            $startDate,
            $endDate,
            $filters['category_id'] ? (int) $filters['category_id'] : null,
            $filters['type']
        );

        $transactions = $reportData['transactions']->sortByDesc('date')->values();
        $incomeTransactions = $reportData['incomeTransactions'];
        $expenseTransactions = $reportData['expenseTransactions'];
        $debts = $reportData['debts']->sortByDesc('due_date')->values();
        $totals = $reportData['totals'];

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
            'incomeTransactions' => $incomeTransactions,
            'expenseTransactions' => $expenseTransactions,
            'debts' => $debts,
            'totalPemasukan' => $totals['pemasukan'],
            'totalPengeluaran' => $totals['pengeluaran'],
            'selisih' => $totals['selisih'],
            'totalHutang' => $totals['hutang'],
            'totalPembayaranHutang' => $totals['pembayaranHutang'],
            'sisaHutang' => $totals['sisaHutang'],
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
        $format = $validated['format'] ?? 'xlsx';
        $currentUser = $request->user();
        $shouldFilterByUser = ! in_array($currentUser->role, [Role::ADMIN, Role::ACCOUNTANT]);

        $fileName = 'Laporan_Keuangan_'.$startDate.'_sampai_'.$endDate.'.'.$format;

        if ($format === 'pdf') {
            $reportData = $this->financialReportService->generate(
                $shouldFilterByUser ? $currentUser->id : null,
                $startDate,
                $endDate,
                isset($validated['category_id']) ? (int) $validated['category_id'] : null,
                $validated['type'] ?? null
            );

            if (app()->runningUnitTests()) {
                return response()
                    ->view('exports.financial_report_pdf', [
                        ...$reportData,
                        'period' => [
                            'start' => $startDate,
                            'end' => $endDate,
                        ],
                    ]);
            }

            return Pdf::loadView('exports.financial_report_pdf', [
                ...$reportData,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ])
                ->setPaper('a4', 'landscape')
                ->stream($fileName);
        }

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
