<?php

namespace App\Http\Controllers;

use App\Models\Transaction;

class DashboardController extends Controller
{
    public function index()
    {
        $summary = Transaction::getSummary();

        return view('dashboard', [
            'title' => 'Dashboard',
            'saldo' => $summary['saldo'],
            'pemasukan' => $summary['pemasukan'],
            'pengeluaran' => $summary['pengeluaran'],
        ]);
    }
}
