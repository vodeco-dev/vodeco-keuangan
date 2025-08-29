<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TransactionController extends Controller
{
    /**
     * Menampilkan daftar semua transaksi dengan filter dan paginasi.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        // Mengambil query builder untuk transaksi, diurutkan dari yang terbaru
        $transactionsQuery = Transaction::with('category')->latest('date');

        // Filter berdasarkan pencarian deskripsi
        if ($request->filled('search')) {
            $transactionsQuery->where('description', 'like', '%' . $request->search . '%');
        }

        // Filter berdasarkan tanggal (diperbaiki dari created_at menjadi date)
        if ($request->filled('date')) {
            $transactionsQuery->whereDate('date', $request->date);
        }

        // Filter berdasarkan ID kategori
        if ($request->filled('category_id')) {
            $transactionsQuery->where('category_id', $request->category_id);
        }

        // Filter berdasarkan tipe kategori (pemasukan/pengeluaran)
        if ($request->filled('type')) {
            $transactionsQuery->whereHas('category', function ($query) use ($request) {
                $query->where('type', $request->type);
            });
        }
        
        // Ambil data transaksi dengan paginasi (10 item per halaman)
        $transactions = $transactionsQuery->paginate(10);

        // Ambil semua kategori untuk dropdown filter
        $categories = Category::orderBy('name')->get();

        // Hitung total pemasukan untuk ringkasan
        $totalPemasukan = Transaction::whereHas('category', function ($query) {
            $query->where('type', 'pemasukan');
        })->sum('amount');

        // Hitung total pengeluaran untuk ringkasan
        $totalPengeluaran = Transaction::whereHas('category', function ($query) {
            $query->where('type', 'pengeluaran');
        })->sum('amount');

        // Hitung saldo
        $saldo = $totalPemasukan - $totalPengeluaran;

        return view('transactions.index', compact('transactions', 'categories', 'totalPemasukan', 'totalPengeluaran', 'saldo'));
    }

    /**
     * Menampilkan form untuk membuat transaksi baru.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        // Ambil semua kategori dan kelompokkan berdasarkan tipe
        $categories = Category::orderBy('name')->get();
        return view('transactions.create', compact('categories'));
    }

    /**
     * Menyimpan transaksi baru ke dalam database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // Validasi data yang masuk dari form (diperbaiki dari created_at menjadi date)
        $request->validate([
            'date' => 'required|date',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        // Buat dan simpan transaksi baru
        Transaction::create($request->all());

        // Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('transactions.index')
                         ->with('success', 'Transaksi berhasil ditambahkan.');
    }

    /**
     * Memperbarui data transaksi di dalam database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        // Validasi data yang masuk dari form (diperbaiki dari created_at menjadi date)
        $request->validate([
            'date' => 'required|date',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        // Update data transaksi
        $transaction->update($request->all());

        // Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('transactions.index')
                         ->with('success', 'Transaksi berhasil diperbarui.');
    }

    /**
     * Menghapus transaksi dari database.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        // Hapus transaksi
        $transaction->delete();

        // Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('transactions.index')
                         ->with('success', 'Transaksi berhasil dihapus.');
    }
}
