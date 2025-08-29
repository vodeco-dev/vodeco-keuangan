<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $summary = Transaction::getSummary();

        $trendsQuery = Transaction::query()
            ->selectRaw('YEAR(date) as year, MONTH(date) as month')
            ->selectRaw('SUM(CASE WHEN categories.type = "pemasukan" THEN amount ELSE 0 END) as pemasukan')
            ->selectRaw('SUM(CASE WHEN categories.type = "pengeluaran" THEN amount ELSE 0 END) as pengeluaran')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $request->user()->id)
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc');

        if ($request->filled('month')) {
            $date = Carbon::parse($request->month);
            $trendsQuery->whereYear('date', $date->year)
                        ->whereMonth('date', $date->month);
        }

        $monthly_trends = $trendsQuery->get();

        $max_value = 0;
        if ($monthly_trends->isNotEmpty()) {
            $max_pemasukan = $monthly_trends->max('pemasukan');
            $max_pengeluaran = $monthly_trends->max('pengeluaran');
            $max_value = max($max_pemasukan, $max_pengeluaran);
        }

        $recent_transactions = Transaction::with('category')
            ->where('user_id', $request->user()->id)
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', [
            'title'               => 'Dashboard',
            'saldo'               => $summary['saldo'],
            'pemasukan'           => $summary['pemasukan'],
            'pengeluaran'         => $summary['pengeluaran'],
            'monthly_trends'      => $monthly_trends,
            'max_trend_value'     => $max_value,
            'recent_transactions' => $recent_transactions,
        ]);
    }
}
