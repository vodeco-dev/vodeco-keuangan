<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // Logika untuk menampilkan halaman laporan
        return view('reports.index');
    }
}