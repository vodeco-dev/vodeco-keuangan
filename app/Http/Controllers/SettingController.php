<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Memperbarui pengaturan aplikasi.
     */
    public function update(Request $request): RedirectResponse
    {
        // Logika inti untuk menyimpan pengaturan
        foreach ($request->except('_token') as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Redirect kembali dengan pesan sukses
        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan berhasil diperbarui.');
    }
}