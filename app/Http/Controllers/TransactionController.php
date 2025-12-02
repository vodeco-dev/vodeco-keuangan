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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService,
        private TransactionProofService $transactionProofService
    )
    {
        $this->authorizeResource(Transaction::class, 'transaction', [
            'except' => ['destroy', 'index', 'show'],
        ]);
    }

    public function index(Request $request): View|JsonResponse
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

        $transactions = $this->transactionService->getAllTransactions($request);
        $categories = Cache::rememberForever('categories', function () {
            return Category::orderBy('name')->get();
        });
        $summary = $this->transactionService->getAllSummary($request);
        $availableMonths = $this->transactionService->getAvailableMonths();

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess([
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
                'summary' => $summary,
                'available_months' => $availableMonths,
            ]);
        }

        return view(
            'transactions.index',
            array_merge(
                compact('transactions', 'categories', 'availableMonths'),
                $summary
            )
        );
    }

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

    public function show(Request $request, Transaction $transaction): View|JsonResponse
    {
        if ($this->isApiRequest($request)) {
            return $this->apiSuccess($transaction->load('category', 'user'));
        }

        return view('transactions.show', compact('transaction'));
    }

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

    public function store(StoreTransactionRequest $request): RedirectResponse|JsonResponse
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

        $transaction = Transaction::create($transactionData);
        $this->transactionService->clearAllSummaryCache();

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess($transaction->load('category', 'user'), 'Transaksi berhasil ditambahkan.', 201);
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse|JsonResponse
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

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess($transaction->load('category', 'user'), 'Transaksi berhasil diperbarui.');
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function showProof(Transaction $transaction)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'UNAUTHORIZED ACTION');
        }

        $isPrivilegedRole = in_array($user->role, [Role::ADMIN, Role::ACCOUNTANT], true);

        if (!$isPrivilegedRole && $user->id !== $transaction->user_id) {
            abort(403, 'UNAUTHORIZED ACTION');
        }

        if (!$transaction->proof_path) {
            abort(404);
        }

        $disk = $transaction->proof_disk ?: 'local';

        if ($disk !== 'local') {
            abort(404);
        }

        $relativePath = ltrim($transaction->proof_path, '/');

        if ($transaction->proof_directory) {
            $relativePath = trim($transaction->proof_directory, '/').'/'.$relativePath;
        }

        if (!Storage::disk($disk)->exists($relativePath)) {
            abort(404);
        }

        return response()->file(Storage::disk($disk)->path($relativePath));
    }

    public function destroy(Request $request, Transaction $transaction): RedirectResponse|JsonResponse
    {
        if ($request->user()->role !== Role::ADMIN) {
            $validated = $request->validate([
                'reason' => ['required', 'string', 'max:500'],
            ]);

            $deletionRequest = TransactionDeletionRequest::create([
                'transaction_id' => $transaction->id,
                'requested_by' => $request->user()->id,
                'status' => 'pending',
                'deletion_reason' => $validated['reason'],
            ]);

            if ($this->isApiRequest($request)) {
                return $this->apiSuccess($deletionRequest, 'Permintaan penghapusan transaksi menunggu persetujuan admin.', 201);
            }

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

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess(null, 'Transaksi berhasil dihapus.');
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil dihapus.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => ['required', 'string', 'in:delete,export'],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['required', 'integer', 'exists:transactions,id'],
        ]);

        $selectedIds = $request->input('selected');
        $action = $request->input('action');
        $isAdmin = $request->user()->role === Role::ADMIN;

        if ($action === 'delete') {
            $transactions = Transaction::whereIn('id', $selectedIds)->get();
            
            foreach ($transactions as $transaction) {
                if ($isAdmin) {
                    $this->authorize('delete', $transaction);
                    $transaction->delete();
                    $this->transactionService->clearAllSummaryCache();
                } else {
                    TransactionDeletionRequest::create([
                        'transaction_id' => $transaction->id,
                        'requested_by' => $request->user()->id,
                        'status' => 'pending',
                        'deletion_reason' => 'Bulk deletion request',
                    ]);
                }
            }

            $message = $isAdmin 
                ? count($selectedIds) . ' transaksi berhasil dihapus.'
                : count($selectedIds) . ' permintaan penghapusan telah dibuat dan menunggu persetujuan admin.';

            return redirect()->route('transactions.index')
                ->with('success', $message);
        }

        if ($action === 'export') {
            return redirect()->route('transactions.index')
                ->with('info', 'Fitur ekspor sedang dalam pengembangan.');
        }

        return redirect()->route('transactions.index')
            ->with('error', 'Aksi tidak valid.');
    }
}
