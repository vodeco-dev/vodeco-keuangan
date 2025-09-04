<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Category;
use App\Models\ServiceCost;
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
        $this->authorizeResource(Transaction::class, 'transaction');
    }

    /**
     * Menampilkan daftar transaksi milik pengguna.
     */
    public function index(Request $request): View
    {
        $transactions = $this->transactionService->getTransactionsForUser($request->user(), $request);
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
        $categories = Category::orderBy('name')->get();
        $serviceCosts = ServiceCost::orderBy('name')->get();
        return view('transactions.create', compact('categories', 'serviceCosts'));
    }

    /**
     * Menyimpan transaksi baru.
     */
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $transactionData = $validated;
        $transactionData['user_id'] = $request->user()->id;

        Transaction::create($transactionData);
        $this->transactionService->clearSummaryCacheForUser($request->user());

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil ditambahkan.');
    }

    /**
     * Memperbarui data transaksi.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validated();

        $transaction->update($validated);
        $this->transactionService->clearSummaryCacheForUser($request->user());

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil diperbarui.');
    }

    /**
     * Menghapus transaksi.
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        $user = $transaction->user;
        $transaction->delete();
        $this->transactionService->clearSummaryCacheForUser($user);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil dihapus.');
    }
}