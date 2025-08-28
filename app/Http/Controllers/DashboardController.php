<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // kirim data ke view kalau perlu
        return view('dashboard', [
            'title' => 'Dashboard',
            'saldo' => 12345.67,
            'pemasukan' => 5678.90,
            'pengeluaran' => 3456.78,
        ]);
    }
}
