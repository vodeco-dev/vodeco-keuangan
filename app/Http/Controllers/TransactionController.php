<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService)
    {
    }

    /**
     * Menampilkan daftar semua transaksi dengan filter dan paginasi.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $transactions = $this->transactionService->getTransactions($request);
        $categories = Category::orderBy('name')->get();
        $summary = $this->transactionService->getSummary();

        return view(
            'transactions.index',
            array_merge(
                compact('transactions', 'categories'),
                $summary
            )
        );
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
