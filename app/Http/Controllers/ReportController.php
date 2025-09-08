<?php

namespace App\Http\Controllers;

use App\Exports\TransactionsExport;
use App\Http\Requests\ReportRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index(ReportRequest $request)
    {
        $validated = $request->validated();
        // Tentukan rentang tanggal.
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        // Ambil transaksi dalam rentang tanggal
        $transactions = Transaction::with('category')
            ->where('user_id', $request->user()->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        // Hitung total pemasukan dan pengeluaran
        $totalPemasukan = $transactions->where('category.type', 'pemasukan')->sum('amount');
        $totalPengeluaran = $transactions->where('category.type', 'pengeluaran')->sum('amount');
        $selisih = $totalPemasukan - $totalPengeluaran;

        // Siapkan data untuk chart
        $chartData = $this->transactionService->prepareChartData($request->user(), $startDate, $endDate);

        return view('reports.index', [
            'title' => 'Laporan',
            'transactions' => $transactions,
            'totalPemasukan' => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'selisih' => $selisih,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'chartData' => $chartData,
        ]);
    }


    // Fungsi untuk handle ekspor
    public function export(ReportRequest $request)
    {
        $validated = $request->validated();
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];
        $format = $validated['format'] ?? 'xlsx'; // xlsx atau csv

        $fileName = 'Laporan_Keuangan_'.$startDate.'_sampai_'.$endDate.'.'.$format;

        return Excel::download(new TransactionsExport($request->user()->id, $startDate, $endDate), $fileName);
    }
}
