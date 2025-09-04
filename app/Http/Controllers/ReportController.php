<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransactionsExport;
use Illuminate\Support\Facades\DB;
use App\Services\TransactionService;

class ReportController extends Controller
{
    public function __construct(private TransactionService $transactionService)
    {
    }

    public function index(Request $request)
    {
        // Tentukan rentang tanggal. Defaultnya adalah bulan ini.
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Ambil transaksi dalam rentang tanggal
        $transactions = Transaction::with('category')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        // Hitung total pemasukan dan pengeluaran
        $totalPemasukan = $transactions->where('category.type', CategoryType::Pemasukan->value)->sum('amount');
        $totalPengeluaran = $transactions->where('category.type', CategoryType::Pengeluaran->value)->sum('amount');
        $selisih = $totalPemasukan - $totalPengeluaran;

        // Hitung AGI
        $agencyGrossIncome = $this->transactionService->getAgencyGrossIncome($request->user());

        // Siapkan data untuk chart
        $chartData = $this->prepareChartData($startDate, $endDate);

        return view('reports.index', [
            'title'            => 'Laporan',
            'transactions'     => $transactions,
            'totalPemasukan'   => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'selisih'          => $selisih,
            'agencyGrossIncome'=> $agencyGrossIncome,
            'startDate'        => $startDate,
            'endDate'          => $endDate,
            'chartData'        => $chartData,
        ]);
    }

    // Fungsi untuk menyiapkan data chart
    private function prepareChartData($startDate, $endDate)
    {
        $pemasukanData = Transaction::whereHas('category', function ($query) {
            $query->where('type', CategoryType::Pemasukan->value);
        })
            ->whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw("DATE(date) as date"), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        $pengeluaranData = Transaction::whereHas('category', function ($query) {
            $query->where('type', CategoryType::Pengeluaran->value);
        })
            ->whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw("DATE(date) as date"), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        $dates = collect();
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);

        while ($currentDate <= $lastDate) {
            $dates->push($currentDate->toDateString());
            $currentDate->addDay();
        }

        return [
            'labels' => $dates->map(function ($date) {
                return Carbon::parse($date)->format('d M');
            }),
            'pemasukan' => $dates->map(function ($date) use ($pemasukanData) {
                return $pemasukanData[$date] ?? 0;
            }),
            'pengeluaran' => $dates->map(function ($date) use ($pengeluaranData) {
                return $pengeluaranData[$date] ?? 0;
            }),
        ];
    }

    // Fungsi untuk handle ekspor
    public function export(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $format = $request->input('format', 'xlsx'); // xlsx atau csv

        $fileName = 'Laporan_Keuangan_' . $startDate . '_sampai_' . $endDate . '.' . $format;

        return Excel::download(new TransactionsExport($startDate, $endDate), $fileName);
    }
}
