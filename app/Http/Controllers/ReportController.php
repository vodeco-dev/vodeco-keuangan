<?php

namespace App\Http\Controllers;

use App\Exports\TransactionsExport;
use App\Http\Requests\ReportRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

        // Hitung AGI
        $agencyGrossIncome = $this->transactionService->getAgencyGrossIncome($request->user());

        // Siapkan data untuk chart
        $chartData = $this->prepareChartData($request->user(), $startDate, $endDate);

        return view('reports.index', [
            'title' => 'Laporan',
            'transactions' => $transactions,
            'totalPemasukan' => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'selisih' => $selisih,
            'agencyGrossIncome' => $agencyGrossIncome,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'chartData' => $chartData,
        ]);
    }

    // Fungsi untuk menyiapkan data chart
    private function prepareChartData(User $user, $startDate, $endDate)
    {
        $pemasukanData = Transaction::where('user_id', $user->id)
            ->whereHas('category', function ($query) {
                $query->where('type', 'pemasukan');
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw('DATE(date) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        $pengeluaranData = Transaction::where('user_id', $user->id)
            ->whereHas('category', function ($query) {
                $query->where('type', 'pengeluaran');
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw('DATE(date) as date'), DB::raw('SUM(amount) as total'))
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
