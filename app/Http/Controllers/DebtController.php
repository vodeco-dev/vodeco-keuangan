<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDebtRequest;
use App\Http\Requests\StoreDebtPaymentRequest;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DebtService;
use App\Services\PassThroughPackageManager;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DebtController extends Controller
{
    protected DebtService $debtService;
    protected TransactionService $transactionService;
    protected PassThroughPackageManager $passThroughPackageManager;

    public function __construct(
        DebtService $debtService,
        TransactionService $transactionService,
        PassThroughPackageManager $passThroughPackageManager
    )
    {
        $this->debtService = $debtService;
        $this->transactionService = $transactionService;
        $this->passThroughPackageManager = $passThroughPackageManager;
        $this->authorizeResource(Debt::class, 'debt');
    }

    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        $canViewAllDebts = in_array($user->role->value, ['admin', 'accountant']);

        $this->autoFailOverdueDebts($user);

        $debts = $this->debtService->getDebts($request, $canViewAllDebts ? null : $user);
        $debts->appends($request->query());

        $summary = $this->debtService->getSummary($canViewAllDebts ? null : $user);

        $incomeCategories = Category::where('type', 'pemasukan')->orderBy('name')->get();
        $expenseCategories = Category::where('type', 'pengeluaran')->orderBy('name')->get();

        $allowedIncomeCategoryIds = $this->getAllowedCategoryIds($user->id, 'income');
        $allowedExpenseCategoryIds = $this->getAllowedCategoryIds($user->id, 'expense');

        $selectableIncomeCategories = $this->filterCategoriesByAllowed($incomeCategories, $allowedIncomeCategoryIds);
        $selectableExpenseCategories = $this->filterCategoriesByAllowed($expenseCategories, $allowedExpenseCategoryIds);

        $passThroughPackages = $this->passThroughPackageManager->all();

        $storedPassThroughCategoryId = Setting::get('pass_through_invoice_category_id');
        $passThroughInvoiceCategoryId = null;

        if (is_numeric($storedPassThroughCategoryId)) {
            $storedPassThroughCategoryId = (int) $storedPassThroughCategoryId;

            if ($selectableIncomeCategories->contains('id', $storedPassThroughCategoryId)) {
                $passThroughInvoiceCategoryId = $storedPassThroughCategoryId;
            }
        }

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess([
                'debts' => $debts->items(),
                'pagination' => [
                    'current_page' => $debts->currentPage(),
                    'last_page' => $debts->lastPage(),
                    'per_page' => $debts->perPage(),
                    'total' => $debts->total(),
                ],
                'summary' => $summary,
            ]);
        }

        return view('debts.index', array_merge([
            'title' => 'Hutang & Piutang',
            'debts' => $debts,
            'incomeCategories' => $incomeCategories,
            'expenseCategories' => $expenseCategories,
            'allowedIncomeCategoryIds' => $allowedIncomeCategoryIds,
            'allowedExpenseCategoryIds' => $allowedExpenseCategoryIds,
            'selectableIncomeCategories' => $selectableIncomeCategories,
            'selectableExpenseCategories' => $selectableExpenseCategories,
            'defaultIncomeCategoryId' => optional($selectableIncomeCategories->first())->id,
            'defaultExpenseCategoryId' => optional($selectableExpenseCategories->first())->id,
            'passThroughPackages' => $passThroughPackages,
            'passThroughInvoiceCategoryId' => $passThroughInvoiceCategoryId,
        ], $summary));
    }

    public function store(StoreDebtRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        if (empty($validated['due_date'])) {
            $validated['due_date'] = now()->addMonths(2)->toDateString();
        }

        $this->ensureCategorySelectionIsAllowed($request->user()->id, $validated['type'], (int) $validated['category_id']);

        $debt = Debt::create(array_merge($validated, [
            'status' => Debt::STATUS_BELUM_LUNAS,
            'user_id' => $request->user()->id,
        ]));

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess($debt->load('payments', 'category'), 'Catatan berhasil ditambahkan.', 201);
        }

        return redirect()->route('debts.index')->with('success', 'Catatan berhasil ditambahkan.');
    }

    public function storePayment(StoreDebtPaymentRequest $request, Debt $debt): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $debt);

        $validated = $request->validated();
        $categoryId = $validated['category_id'] ?? null;

        if ($categoryId) {
            $this->ensureCategorySelectionIsAllowed($request->user()->id, $debt->type, (int) $categoryId);
        }

        try {
            DB::transaction(function () use ($validated, $request, $debt, $categoryId) {
                // Set category first if provided
                if ($categoryId) {
                    $category = $this->resolveCategoryForDebt($debt, (int) $categoryId);
                    $debt->category()->associate($category);
                    $debt->save();
                }

                // Make sure category is set before creating payment
                if (!$debt->category_id) {
                    throw ValidationException::withMessages([
                        'category_id' => 'Kategori wajib dipilih untuk mencatat pembayaran.',
                    ]);
                }

                $debt->load('payments');

                // Update invoice info if exists
                $invoice = $debt->invoice;
                if ($invoice) {
                    $invoice->loadMissing('items');

                    $downPaymentTotal = $debt->payments->sum('amount') + $validated['payment_amount'];
                    $invoice->down_payment = min($invoice->total, $downPaymentTotal);
                    $invoice->payment_date = $validated['payment_date'] ?? now();

                    if ($invoice->down_payment >= $invoice->total && $invoice->total > 0) {
                        $invoice->status = 'lunas';
                    } elseif ($invoice->down_payment > 0) {
                        $invoice->status = 'belum lunas';
                    } else {
                        $invoice->status = 'belum bayar';
                    }

                    $invoice->save();

                    $debt->description = $invoice->transactionDescription();
                    $debt->amount = $invoice->total;
                    $debt->due_date = $invoice->due_date;
                    $debt->related_party = $invoice->client_name
                        ?: ($invoice->client_whatsapp ?: 'Klien Invoice #' . $invoice->number);
                }

                // Determine if this payment should create a transaction
                $canEnterTransaction = true;
                $isPassThroughDebt = $debt->type === Debt::TYPE_PASS_THROUGH;
                $isDownPaymentDebt = $debt->type === Debt::TYPE_DOWN_PAYMENT;

                if ($debt->invoice_id) {
                    $debt->loadMissing('invoice');
                    if ($debt->invoice) {
                        $canEnterTransaction = $debt->invoice->canEnterTransactionWhenPaid();
                    }
                }

                // Create transaction for this payment
                $transaction = null;
                if ($isPassThroughDebt || $canEnterTransaction) {
                    $description = 'Pembayaran: ' . $debt->description;
                    if ($debt->invoice_id && $debt->invoice) {
                        $invoiceNumber = '(' . $debt->invoice->number . ')';
                        if (strpos($description, $invoiceNumber) === false) {
                            $description = $description . ' ' . $invoiceNumber;
                        }
                    }

                    // Determine transaction category based on debt type
                    $transactionCategoryId = $debt->category_id;
                    
                    if ($isPassThroughDebt) {
                        // Pass through (iklan) goes to expense (pengeluaran)
                        $expenseCategory = Category::where('type', 'pengeluaran')
                            ->where('name', 'like', '%iklan%')
                            ->first();

                        if (!$expenseCategory) {
                            $expenseCategory = Category::where('type', 'pengeluaran')
                                ->where('name', 'like', '%pengeluaran%')
                                ->first();
                        }

                        if ($expenseCategory) {
                            $transactionCategoryId = $expenseCategory->id;
                        }
                    } elseif ($isDownPaymentDebt) {
                        // Down payment goes to income (pemasukan)
                        // Use the debt's category if it's already income type, otherwise find income category
                        $debtCategory = Category::find($debt->category_id);
                        if ($debtCategory && $debtCategory->type !== 'pemasukan') {
                            $incomeCategory = Category::where('type', 'pemasukan')
                                ->where('name', 'like', '%down%payment%')
                                ->first();

                            if (!$incomeCategory) {
                                $incomeCategory = Category::where('type', 'pemasukan')
                                    ->where('name', 'like', '%pemasukan%')
                                    ->first();
                            }

                            if ($incomeCategory) {
                                $transactionCategoryId = $incomeCategory->id;
                            }
                        }
                    }

                    $transaction = Transaction::create([
                        'category_id' => $transactionCategoryId,
                        'date' => $validated['payment_date'] ?? now(),
                        'amount' => $validated['payment_amount'],
                        'description' => $description,
                        'user_id' => $request->user()->id,
                    ]);

                    $this->transactionService->clearSummaryCacheForUser($request->user());
                }

                // Create payment and link to transaction
                $payment = $debt->payments()->create([
                    'amount' => $validated['payment_amount'],
                    'payment_date' => $validated['payment_date'] ?? now(),
                    'notes' => $validated['notes'] ?? null,
                    'transaction_id' => $transaction ? $transaction->id : null,
                ]);

                // Update debt status
                $debt->load('payments');
                if ($debt->paid_amount >= $debt->amount) {
                    $debt->status = Debt::STATUS_LUNAS;
                } else {
                    $debt->status = Debt::STATUS_BELUM_LUNAS;
                }

                $debt->save();
            });

            if ($this->isApiRequest($request)) {
                return $this->apiSuccess($debt->load('payments', 'category'), 'Pembayaran berhasil dicatat.');
            }

            return redirect()->route('debts.index')->with('success', 'Pembayaran berhasil dicatat.');
        } catch (ValidationException $e) {
            if ($this->isApiRequest($request)) {
                return $this->apiError('Validation failed', 422, $e->errors());
            }
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error saat menyimpan pembayaran hutang', [
                'debt_id' => $debt->id,
                'payment_amount' => $validated['payment_amount'] ?? null,
                'payment_date' => $validated['payment_date'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            if ($this->isApiRequest($request)) {
                return $this->apiError('Terjadi kesalahan saat menyimpan pembayaran.', 500);
            }
            
            $errorMessage = config('app.debug') 
                ? 'Terjadi kesalahan: ' . $e->getMessage()
                : 'Terjadi kesalahan saat menyimpan pembayaran.';
            
            return back()->withErrors($errorMessage);
        }
    }

    public function show(Request $request, Debt $debt): View|JsonResponse
    {
        if ($this->isApiRequest($request)) {
            return $this->apiSuccess($debt->load('payments', 'category', 'invoice'));
        }

        return view('debts.show', compact('debt'));
    }

    public function edit(Debt $debt): View
    {
        $this->authorize('update', $debt);

        return view('debts.edit', [
            'debt' => $debt,
        ]);
    }

    public function update(Request $request, Debt $debt): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $debt);

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'related_party' => 'required|string|max:255',
            'due_date' => 'nullable|date',
        ]);

        $debt->update($validated);

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess($debt->load('payments', 'category'), 'Catatan berhasil diperbarui.');
        }

        return redirect()->route('debts.index')->with('success', 'Catatan berhasil diperbarui.');
    }

    public function markAsFailed(Request $request, Debt $debt): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $debt);

        if ($debt->status !== Debt::STATUS_BELUM_LUNAS) {
            $message = 'Catatan ini tidak dapat ditandai gagal.';
            if ($this->isApiRequest($request)) {
                return $this->apiError($message, 422);
            }
            return redirect()->route('debts.index')->with('info', $message);
        }

        if (!$debt->category_id) {
            $message = 'Tidak dapat menandai gagal karena tidak ada kategori yang terhubung. Silakan lakukan pembayaran parsial terlebih dahulu untuk menyambungkan kategori.';
            if ($this->isApiRequest($request)) {
                return $this->apiError($message, 422);
            }
            return redirect()->route('debts.index')->withErrors(['error' => $message]);
        }

        $this->finalizeFailedDebt($debt, $request->user());

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess($debt->load('payments', 'category'), 'Catatan ditandai sebagai gagal project.');
        }

        return redirect()->route('debts.index')->with('success', 'Catatan ditandai sebagai gagal project.');
    }

    protected function autoFailOverdueDebts(User $user): void
    {
        $overdueDebts = Debt::where('user_id', $user->id)
            ->where('status', Debt::STATUS_BELUM_LUNAS)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->get();

        foreach ($overdueDebts as $debt) {
            $this->finalizeFailedDebt($debt, $user, true);
        }
    }

    protected function finalizeFailedDebt(Debt $debt, User $user, bool $auto = false): void
    {
        if (!$debt->category_id) {
            return;
        }

        DB::transaction(function () use ($debt, $user, $auto) {
            $debt->loadMissing('payments');

            if ($debt->status !== Debt::STATUS_BELUM_LUNAS) {
                return;
            }

            $debt->status = Debt::STATUS_GAGAL;
            $debt->save();

            $remainingAmount = max(0, $debt->amount - $debt->paid_amount);

            if ($remainingAmount <= 0) {
                return;
            }

            $isPassThroughDebt = $debt->type === Debt::TYPE_PASS_THROUGH;

            if ($isPassThroughDebt) {
                $description = 'Iklan Gagal: ' . $debt->description;
                if ($debt->invoice_id) {
                    $debt->loadMissing('invoice');
                    if ($debt->invoice) {
                        $invoiceNumber = '(' . $debt->invoice->number . ')';
                        if (strpos($description, $invoiceNumber) === false) {
                            $description = $description . ' ' . $invoiceNumber;
                        }
                    }
                }

                $incomeCategory = Category::where('type', 'pemasukan')
                    ->where('name', 'like', '%iklan%')
                    ->first();

                if (!$incomeCategory) {
                    $incomeCategory = Category::where('type', 'pemasukan')
                        ->where('name', 'like', '%penjualan%')
                        ->first();
                }

                $transactionCategoryId = $incomeCategory ? $incomeCategory->id : $debt->category_id;

                Transaction::create([
                    'category_id' => $transactionCategoryId,
                    'date' => now(),
                    'amount' => $remainingAmount,
                    'description' => $description,
                    'user_id' => $user->id,
                ]);
            } else {
                $description = ($auto ? '[Otomatis] ' : '') . 'Gagal Project: ' . $debt->description;
                if ($debt->invoice_id) {
                    $debt->loadMissing('invoice');
                    if ($debt->invoice) {
                        $invoiceNumber = '(' . $debt->invoice->number . ')';
                        if (strpos($description, $invoiceNumber) === false) {
                            $description = $description . ' ' . $invoiceNumber;
                        }
                    }
                }

                Transaction::create([
                    'category_id' => $debt->category_id,
                    'date' => now(),
                    'amount' => $remainingAmount,
                    'description' => $description,
                    'user_id' => $user->id,
                ]);
            }

            $this->transactionService->clearSummaryCacheForUser($user);
        });
    }

    public function updateCategoryPreferences(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'allowed_income_categories' => ['nullable', 'array'],
            'allowed_income_categories.*' => [
                'integer',
                Rule::exists('categories', 'id')->where('type', 'pemasukan'),
            ],
            'allowed_expense_categories' => ['nullable', 'array'],
            'allowed_expense_categories.*' => [
                'integer',
                Rule::exists('categories', 'id')->where('type', 'pengeluaran'),
            ],
        ]);

        $this->storeCategoryPreference($user->id, 'income', $validated['allowed_income_categories'] ?? []);
        $this->storeCategoryPreference($user->id, 'expense', $validated['allowed_expense_categories'] ?? []);

        return redirect()->route('debts.index')->with('success', 'Pengaturan kategori berhasil diperbarui.');
    }

    public function bulkDelete(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'debt_ids' => 'required|array',
            'debt_ids.*' => 'required|integer|exists:debts,id',
        ]);

        $debtIds = $validated['debt_ids'];

        $debts = Debt::whereIn('id', $debtIds)->get();
        foreach ($debts as $debt) {
            $this->authorize('delete', $debt);
        }

        Debt::whereIn('id', $debtIds)->delete();

        $count = count($debtIds);

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess(['deleted_count' => $count], "{$count} catatan berhasil dihapus.");
        }

        return redirect()->route('debts.index')->with('success', "{$count} catatan berhasil dihapus.");
    }

    public function bulkMarkAsFailed(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'debt_ids' => 'required|array',
            'debt_ids.*' => 'required|integer|exists:debts,id',
        ]);

        $debtIds = $validated['debt_ids'];

        $debts = Debt::whereIn('id', $debtIds)->get();
        foreach ($debts as $debt) {
            $this->authorize('update', $debt);

            if ($debt->status !== Debt::STATUS_BELUM_LUNAS) {
                if ($this->isApiRequest($request)) {
                    return $this->apiError("Debt {$debt->id} tidak dapat ditandai gagal karena statusnya sudah final.", 422);
                }
                return redirect()->route('debts.index')->with('info', "Debt {$debt->id} tidak dapat ditandai gagal karena statusnya sudah final.");
            }

            if (!$debt->category_id) {
                if ($this->isApiRequest($request)) {
                    return $this->apiError("Debt {$debt->id} tidak dapat ditandai gagal karena tidak ada kategori yang terhubung.", 422);
                }
                return redirect()->route('debts.index')->withErrors(['error' => "Debt {$debt->id} tidak dapat ditandai gagal karena tidak ada kategori yang terhubung."]);
            }
        }

        $processedCount = 0;
        DB::transaction(function () use ($debts, &$processedCount) {
            foreach ($debts as $debt) {
                $this->finalizeFailedDebt($debt, $debt->user);
                $processedCount++;
            }
        });

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess(['processed_count' => $processedCount], "{$processedCount} catatan berhasil ditandai sebagai gagal.");
        }

        return redirect()->route('debts.index')->with('success', "{$processedCount} catatan berhasil ditandai sebagai gagal.");
    }

    public function destroy(Request $request, Debt $debt): RedirectResponse|JsonResponse
    {
        $debt->delete();

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess(null, 'Catatan berhasil dihapus.');
        }

        return redirect()->route('debts.index')->with('success', 'Catatan berhasil dihapus.');
    }

    private function getAllowedCategoryIds(int $userId, string $type): Collection
    {
        $key = $this->getCategoryPreferenceKey($userId, $type);
        $raw = Setting::get($key, '[]');
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return collect();
        }

        return collect($decoded)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
    }

    private function filterCategoriesByAllowed(Collection $categories, Collection $allowedIds): Collection
    {
        if ($allowedIds->isEmpty()) {
            return $categories;
        }

        return $categories
            ->filter(fn ($category) => $allowedIds->contains($category->id))
            ->values();
    }

    private function storeCategoryPreference(int $userId, string $type, array $categoryIds): void
    {
        $key = $this->getCategoryPreferenceKey($userId, $type);
        $normalized = collect($categoryIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($normalized->all())]
        );

        Cache::forget('setting:' . $key);
    }

    private function getCategoryPreferenceKey(int $userId, string $type): string
    {
        return match ($type) {
            'income' => 'debt_allowed_income_categories_user_' . $userId,
            'expense' => 'debt_allowed_expense_categories_user_' . $userId,
            default => throw new \InvalidArgumentException('Tipe kategori tidak valid.'),
        };
    }

    private function getExpectedCategoryType(string $debtType): string
    {
        return $debtType === Debt::TYPE_DOWN_PAYMENT ? 'pemasukan' : 'pengeluaran';
    }

    private function ensureCategorySelectionIsAllowed(int $userId, string $debtType, int $categoryId): void
    {
        $allowedIds = $debtType === Debt::TYPE_DOWN_PAYMENT
            ? $this->getAllowedCategoryIds($userId, 'income')
            : $this->getAllowedCategoryIds($userId, 'expense');

        if ($allowedIds->isNotEmpty() && !$allowedIds->contains($categoryId)) {
            throw ValidationException::withMessages([
                'category_id' => 'Kategori yang dipilih tidak tersedia pada pengaturan.',
            ]);
        }
    }

    private function resolveCategoryForDebt(Debt $debt, int $categoryId): Category
    {
        $expectedType = $this->getExpectedCategoryType($debt->type);

        $category = Category::whereKey($categoryId)
            ->where('type', $expectedType)
            ->first();

        if (!$category) {
            throw ValidationException::withMessages([
                'category_id' => 'Kategori tidak sesuai dengan tipe catatan.',
            ]);
        }

        return $category;
    }

    public function syncCompletedDebtsToTransactions(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('viewAny', Debt::class);

        $count = $this->debtService->syncCompletedDebtsToTransactions();

        $this->transactionService->clearAllSummaryCaches();

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess([
                'count' => $count,
            ], "Successfully created {$count} transaction record(s) from completed debts.");
        }

        return redirect()->route('debts.index')
            ->with('success', "Berhasil membuat {$count} catatan transaksi dari hutang yang sudah lunas.");
    }

    public function fixInconsistentDebtStatuses(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('viewAny', Debt::class);

        $count = $this->debtService->fixInconsistentDebtStatuses();

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess([
                'count' => $count,
            ], "Successfully fixed {$count} debt status(es).");
        }

        return redirect()->route('debts.index')
            ->with('success', "Berhasil memperbaiki {$count} status hutang yang tidak konsisten.");
    }

    public function syncMissingDebts(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('viewAny', Debt::class);

        $count = $this->debtService->syncMissingDebts();

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess([
                'count' => $count,
            ], "Successfully created {$count} debt record(s).");
        }

        return redirect()->route('debts.index')
            ->with('success', "Berhasil membuat {$count} catatan hutang yang hilang.");
    }

    public function backfillPaymentTransactions(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('viewAny', Debt::class);

        $result = $this->debtService->backfillPaymentTransactions();

        $this->transactionService->clearAllSummaryCache();

        $message = sprintf(
            'Backfill selesai: %d transaksi dibuat, %d transaksi yang ada di-link, %d pembayaran dilewati dari total %d pembayaran.',
            $result['created'],
            $result['linked'],
            $result['skipped'],
            $result['total']
        );

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess($result, $message);
        }

        return redirect()->route('debts.index')->with('success', $message);
    }
}
