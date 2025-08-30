<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    /**
     * Menggabungkan Service Layer dan Authorization.
     */
    public function __construct(private TransactionService $transactionService)
    {
        // Menerapkan keamanan otorisasi dari branch 'codex'
        $this->authorizeResource(Transaction::class, 'transaction');
    }

    /**
     * Menampilkan daftar transaksi milik pengguna.
     * Menggunakan TransactionService dari branch 'main' yang sudah diamankan.
     */
    public function index(Request $request): View
    {
        $transactions = $this->transactionService->getTransactionsForUser($request->user(), $request);
        // Mengambil semua kategori untuk filter, bukan hanya milik user
        $categories = Category::orderBy('name')->get();
        $summary = $this->transactionService->getSummaryForUser($request->user());

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
     */
    public function create(Request $request): View
    {
        // Mengambil semua kategori agar bisa dipilih, bukan hanya milik user
        $categories = Category::orderBy('name')->get();
        return view('transactions.create', compact('categories'));
    }

    /**
     * Menyimpan transaksi baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'date' => 'required|date',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        // PERUBAHAN 1: Ganti pencarian kategori agar global
        // Kategori dicari tanpa filter user_id
        $category = Category::findOrFail($request->category_id);

        $transactionData = $request->all();
        // PERUBAHAN 2: Pastikan user_id tetap diisi
        $transactionData['user_id'] = $request->user()->id;
        $transactionData['category_id'] = $category->id;

        Transaction::create($transactionData);
        $this->transactionService->clearSummaryCacheForUser($request->user());

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil ditambahkan.');
    }

    /**
     * Memperbarui data transaksi.
     */
    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $request->validate([
            'date' => 'required|date',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);
        
        // PERUBAHAN 1: Ganti pencarian kategori agar global
        // Kategori dicari tanpa filter user_id
        $category = Category::findOrFail($request->category_id);
        
        $updateData = $request->all();
        $updateData['category_id'] = $category->id;
        // PERUBAHAN 2: user_id tidak perlu diubah karena sudah ada di $transaction
        // dan tidak termasuk dalam $request->all()

        $transaction->update($updateData);
        $this->transactionService->clearSummaryCacheForUser($request->user());

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil diperbarui.');
    }

    /**
     * Menghapus transaksi.
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        // Otorisasi sudah ditangani oleh authorizeResource di constructor
        $transaction->delete();
        $this->transactionService->clearSummaryCacheForUser($transaction->user);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil dihapus.');
    }
}