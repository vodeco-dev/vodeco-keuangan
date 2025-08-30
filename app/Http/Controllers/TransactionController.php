<?php

namespace App\Http\Controllers;

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
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'date' => 'required|date',
            'category_id' => 'required|exists:categories,id',
            'service_cost_id' => 'nullable|exists:service_costs,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $category = Category::findOrFail($request->category_id);

        $transactionData = $request->only(['date', 'category_id', 'service_cost_id', 'amount', 'description']);
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
            'service_cost_id' => 'nullable|exists:service_costs,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $category = Category::findOrFail($request->category_id);

        $updateData = $request->only(['date', 'category_id', 'service_cost_id', 'amount', 'description']);
        $updateData['category_id'] = $category->id;

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
        $transaction->delete();
        $this->transactionService->clearSummaryCacheForUser($transaction->user);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil dihapus.');
    }
}