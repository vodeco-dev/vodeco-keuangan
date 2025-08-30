<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransactionsExport;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // Tentukan rentang tanggal. Defaultnya adalah bulan ini.
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Ambil transaksi dalam rentang tanggal
        $transactions = Transaction::with(['category', 'project.client'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        // Hitung total pemasukan dan pengeluaran
        $totalPemasukan = $transactions->where('category.type', 'pemasukan')->sum('amount');
        $totalPengeluaran = $transactions->where('category.type', 'pengeluaran')->sum('amount');
        $selisih = $totalPemasukan - $totalPengeluaran;

        // Siapkan data untuk chart
        $chartData = $this->prepareChartData($startDate, $endDate);

        $projectReports = Transaction::select('projects.name as project_name', 'clients.name as client_name',
                DB::raw("SUM(CASE WHEN categories.type = 'pemasukan' THEN transactions.amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN categories.type = 'pengeluaran' THEN transactions.amount ELSE 0 END) as expense"))
            ->join('projects', 'transactions.project_id', '=', 'projects.id')
            ->join('clients', 'projects.client_id', '=', 'clients.id')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->groupBy('projects.id', 'projects.name', 'clients.name')
            ->get();

        $clientReports = Transaction::select('clients.name as client_name',
                DB::raw("SUM(CASE WHEN categories.type = 'pemasukan' THEN transactions.amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN categories.type = 'pengeluaran' THEN transactions.amount ELSE 0 END) as expense"))
            ->join('projects', 'transactions.project_id', '=', 'projects.id')
            ->join('clients', 'projects.client_id', '=', 'clients.id')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->groupBy('clients.id', 'clients.name')
            ->get();

        return view('reports.index', [
            'title' => 'Laporan',
            'transactions' => $transactions,
            'totalPemasukan' => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'selisih' => $selisih,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'chartData' => $chartData,
            'projectReports' => $projectReports,
            'clientReports' => $clientReports,
        ]);
    }

    // Fungsi untuk menyiapkan data chart
    private function prepareChartData($startDate, $endDate)
    {
        $pemasukanData = Transaction::whereHas('category', function ($query) {
                $query->where('type', 'pemasukan');
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw("DATE(date) as date"), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        $pengeluaranData = Transaction::whereHas('category', function ($query) {
                $query->where('type', 'pengeluaran');
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
            'labels' => $dates->map(function($date) {
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