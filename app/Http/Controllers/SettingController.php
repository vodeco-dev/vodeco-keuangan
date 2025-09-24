<?php

namespace App\Http\Controllers;

use App\Exports\TransactionsExport;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

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

    public function display(): View
    {
        return view('settings.display', [
            'title' => 'Preferensi Tampilan',
            'theme' => Setting::get('theme', 'light'),
        ]);
    }

    public function notifications(): View
    {
        return view('settings.notifications', [
            'title' => 'Pengingat',
            'notify_transaction_approved' => (bool) Setting::get('notify_transaction_approved', false),
            'notify_transaction_deleted' => (bool) Setting::get('notify_transaction_deleted', false),
        ]);
    }

    public function storage(): View
    {
        return view('settings.storage', [
            'title' => 'Penyimpanan Bukti Transaksi',
            'transaction_proof_server_directory' => Setting::get('transaction_proof_server_directory', 'transaction-proofs'),
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
            Cache::forget('setting:'.$key);
        }

        // Redirect kembali dengan pesan sukses
        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan berhasil diperbarui.');
    }

    public function updateDisplay(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => 'required|in:light,dark',
        ]);

        Setting::updateOrCreate(['key' => 'theme'], ['value' => $validated['theme']]);
        Cache::forget('setting:theme');

        return redirect()->route('settings.display')
            ->with('success', 'Preferensi tampilan diperbarui.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $request->validate([
            'notify_transaction_approved' => 'nullable|boolean',
            'notify_transaction_deleted' => 'nullable|boolean',
        ]);

        Setting::updateOrCreate(['key' => 'notify_transaction_approved'], ['value' => $request->boolean('notify_transaction_approved')]);
        Cache::forget('setting:notify_transaction_approved');
        Setting::updateOrCreate(['key' => 'notify_transaction_deleted'], ['value' => $request->boolean('notify_transaction_deleted')]);
        Cache::forget('setting:notify_transaction_deleted');

        return redirect()->route('settings.notifications')
            ->with('success', 'Pengingat diperbarui.');
    }

    public function updateStorage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_proof_server_directory' => 'nullable|string|max:255',
        ]);

        $serverDirectory = isset($validated['transaction_proof_server_directory'])
            ? trim($validated['transaction_proof_server_directory'])
            : '';

        Setting::updateOrCreate(
            ['key' => 'transaction_proof_server_directory'],
            ['value' => $serverDirectory]
        );
        Cache::forget('setting:transaction_proof_server_directory');

        // Since storage is forced to server, we can optionally clean up the old setting
        if (Setting::get('transaction_proof_storage') === 'drive') {
            Setting::updateOrCreate(['key' => 'transaction_proof_storage'], ['value' => 'server']);
            Cache::forget('setting:transaction_proof_storage');
        }

        return redirect()->route('settings.storage')
            ->with('success', 'Pengaturan penyimpanan bukti transaksi diperbarui.');
    }

    public function data(): View
    {
        return view('settings.data', [
            'title' => 'Manajemen Data',
        ]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:xlsx,csv',
        ]);

        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];
        $format = $validated['format'];

        $fileName = 'Laporan_Transaksi_'.$startDate.'_sampai_'.$endDate.'.'.$format;

        return Excel::download(new TransactionsExport($request->user()->id, $startDate, $endDate), $fileName);
    }
}
