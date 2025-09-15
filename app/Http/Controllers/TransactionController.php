<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Category;
use App\Enums\Role;
use App\Models\Transaction;
use App\Models\TransactionDeletionRequest;
use App\Models\Setting;
use App\Notifications\TransactionDeleted;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class TransactionController extends Controller
{
    /**
     * Menggabungkan Service Layer dan Authorization.
     */
    public function __construct(private TransactionService $transactionService)
    {
        $this->authorizeResource(Transaction::class, 'transaction', [
            'except' => ['destroy', 'index'],
        ]);
    }

    /**
     * Menampilkan daftar semua transaksi.
     */
    public function index(Request $request): View
    {
        // Eager loading for 'category' and 'user' relationships is handled
        // in the TransactionService::getAllTransactions() method to prevent N+1 queries.
        $transactions = $this->transactionService->getAllTransactions($request);
        $categories = Cache::rememberForever('categories', function () {
            return Category::orderBy('name')->get();
        });
        $summary = $this->transactionService->getAllSummary();

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
        $categories = Cache::rememberForever('categories', function () {
            return Category::orderBy('name')->get();
        });
        return view('transactions.create', compact('categories'));
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
        $this->transactionService->clearAllSummaryCache();

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
        $this->transactionService->clearAllSummaryCache();

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil diperbarui.');
    }

    /**
     * Menghapus transaksi.
     */
    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        if ($request->user()->role !== Role::ADMIN) {
            TransactionDeletionRequest::create([
                'transaction_id' => $transaction->id,
                'requested_by' => $request->user()->id,
                'status' => 'pending',
            ]);

            return redirect()->route('transactions.index')
                ->with('success', 'Permintaan penghapusan transaksi menunggu persetujuan admin.');
        }

        $this->authorize('delete', $transaction);

        $user = $transaction->user;
        $transaction->delete();
        $this->transactionService->clearAllSummaryCache();

        if (Setting::get('notify_transaction_deleted')) {
            $user->notify(new TransactionDeleted($transaction));
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil dihapus.');
    }
}
