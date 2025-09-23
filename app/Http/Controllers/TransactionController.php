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
use App\Services\TransactionProofService;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class TransactionController extends Controller
{
    /**
     * Menggabungkan Service Layer dan Authorization.
     */
    public function __construct(
        private TransactionService $transactionService,
        private TransactionProofService $transactionProofService
    )
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
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $request->request->remove('month');
            $request->request->remove('year');
        } else {
            $now = now();
            $defaults = [];

            if (!$request->has('month')) {
                $defaults['month'] = $now->month;
            }

            if (!$request->has('year')) {
                $defaults['year'] = $now->year;
            }

            if (!empty($defaults)) {
                $request->merge($defaults);
            }
        }

        // Eager loading for 'category' and 'user' relationships is handled
        // in the TransactionService::getAllTransactions() method to prevent N+1 queries.
        $transactions = $this->transactionService->getAllTransactions($request);
        $categories = Cache::rememberForever('categories', function () {
            return Category::orderBy('name')->get();
        });
        $summary = $this->transactionService->getAllSummary($request);
        $availableMonths = $this->transactionService->getAvailableMonths();

        return view(
            'transactions.index',
            array_merge(
                compact('transactions', 'categories', 'availableMonths'),
                $summary
            )
        );
    }

    /**
     * Menampilkan form untuk membuat transaksi baru.
     */
    public function create(Request $request): View
    {
        $incomeCategories = Cache::rememberForever('income_categories', function () {
            return Category::where('type', 'pemasukan')->orderBy('name')->get();
        });
        $expenseCategories = Cache::rememberForever('expense_categories', function () {
            return Category::where('type', 'pengeluaran')->orderBy('name')->get();
        });

        return view('transactions.create', compact('incomeCategories', 'expenseCategories'));
    }

    /**
     * Menampilkan form untuk mengubah transaksi.
     */
    public function edit(Transaction $transaction): View
    {
        $incomeCategories = Cache::rememberForever('income_categories', function () {
            return Category::where('type', 'pemasukan')->orderBy('name')->get();
        });
        $expenseCategories = Cache::rememberForever('expense_categories', function () {
            return Category::where('type', 'pengeluaran')->orderBy('name')->get();
        });

        return view('transactions.edit', [
            'transaction' => $transaction,
            'incomeCategories' => $incomeCategories,
            'expenseCategories' => $expenseCategories,
        ]);
    }

    /**
     * Menyimpan transaksi baru.
     */
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $transactionData = Arr::except($validated, ['proof', 'proof_name']);
        $category = Category::findOrFail($transactionData['category_id']);
        $transactionDate = Carbon::parse($transactionData['date']);

        $categoryType = $category->type ?? 'lainnya';

        $proofData = $this->transactionProofService->prepareForStore(
            $request->file('proof'),
            $request->input('proof_name'),
            $transactionDate,
            $categoryType
        );

        $transactionData['user_id'] = $request->user()->id;
        $transactionData = array_merge($transactionData, $proofData);

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

        $updateData = Arr::except($validated, ['proof', 'proof_name']);
        $category = Category::findOrFail($updateData['category_id']);
        $transactionDate = Carbon::parse($updateData['date']);

        $categoryType = $category->type ?? 'lainnya';

        $proofData = $this->transactionProofService->handleUpdate(
            $transaction,
            $request->file('proof'),
            $request->input('proof_name'),
            $transactionDate,
            $categoryType
        );

        $updateData = array_merge($updateData, $proofData);

        $transaction->update($updateData);
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
            $validated = $request->validate([
                'reason' => ['required', 'string', 'max:500'],
            ]);

            TransactionDeletionRequest::create([
                'transaction_id' => $transaction->id,
                'requested_by' => $request->user()->id,
                'status' => 'pending',
                'deletion_reason' => $validated['reason'],
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
