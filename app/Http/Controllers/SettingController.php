<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Menampilkan halaman utama pengaturan.
     */
    public function index()
    {
        return view('settings.index', [
            'title' => 'Pengaturan',
        ]);
    }
}