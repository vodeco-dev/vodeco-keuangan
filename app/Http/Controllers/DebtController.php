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
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB; // Diambil dari branch 'main'
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DebtController extends Controller
{
    protected DebtService $debtService;
    protected TransactionService $transactionService;

    /**
     * Terapkan authorization policy ke semua method resource controller.
     * Diambil dari branch 'codex/...'
     */
    public function __construct(
        DebtService $debtService,
        TransactionService $transactionService
    )
    {
        $this->debtService = $debtService;
        $this->transactionService = $transactionService;
        $this->authorizeResource(Debt::class, 'debt');
    }

    /**
     * Menampilkan daftar hutang & piutang milik pengguna yang sedang login.
     * Menggabungkan logika query dari 'codex/...'
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $this->autoFailOverdueDebts($user);

        $debts = $this->debtService->getDebts($request, $user);
        $debts->appends($request->query());

        $summary = $this->debtService->getSummary($user);

        $incomeCategories = Category::where('type', 'pemasukan')->orderBy('name')->get();
        $expenseCategories = Category::where('type', 'pengeluaran')->orderBy('name')->get();

        $allowedIncomeCategoryIds = $this->getAllowedCategoryIds($user->id, 'income');
        $allowedExpenseCategoryIds = $this->getAllowedCategoryIds($user->id, 'expense');

        $selectableIncomeCategories = $this->filterCategoriesByAllowed($incomeCategories, $allowedIncomeCategoryIds);
        $selectableExpenseCategories = $this->filterCategoriesByAllowed($expenseCategories, $allowedExpenseCategoryIds);

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
        ], $summary));
    }

    /**
     * Menyimpan catatan hutang/piutang baru.
     */
    public function store(StoreDebtRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (empty($validated['due_date'])) {
            $validated['due_date'] = now()->addMonths(2)->toDateString();
        }

        $this->ensureCategorySelectionIsAllowed($request->user()->id, $validated['type'], (int) $validated['category_id']);

        Debt::create(array_merge($validated, [
            'status' => Debt::STATUS_BELUM_LUNAS,
            'user_id' => $request->user()->id, // Keamanan: Pastikan data baru memiliki pemilik
        ]));

        return redirect()->route('debts.index')->with('success', 'Catatan berhasil ditambahkan.');
    }

    /**
     * Menyimpan pembayaran/cicilan baru.
     * Menggabungkan authorize, DB::transaction, dan try-catch dari kedua branch.
     */
    public function storePayment(StoreDebtPaymentRequest $request, Debt $debt): RedirectResponse
    {
        // Keamanan: Pastikan user boleh mengupdate data ini
        $this->authorize('update', $debt);

        $validated = $request->validated();
        $categoryId = $validated['category_id'] ?? null;

        if ($categoryId) {
            $this->ensureCategorySelectionIsAllowed($request->user()->id, $debt->type, (int) $categoryId);
        }

        try {
            // Keandalan: Pastikan semua operasi database berhasil atau tidak sama sekali
            DB::transaction(function () use ($validated, $request, $debt, $categoryId) {
                $debt->payments()->create([
                    'amount' => $validated['payment_amount'],
                    'payment_date' => $validated['payment_date'] ?? now(),
                    'notes' => $validated['notes'] ?? null,
                ]);

                if ($categoryId) {
                    $category = $this->resolveCategoryForDebt($debt, (int) $categoryId);
                    $debt->category()->associate($category);
                    $debt->save();
                }

                // Reload relasi untuk mendapatkan paid_amount yang ter-update
                $debt->load('payments');

                $invoice = $debt->invoice;
                if ($invoice) {
                    $invoice->loadMissing('items');

                    $downPaymentTotal = $debt->payments->sum('amount');
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

                // Cek jika sudah lunas
                if ($debt->paid_amount >= $debt->amount) {
                    if (!$debt->category_id) {
                        throw ValidationException::withMessages([
                            'category_id' => 'Kategori wajib dipilih untuk mencatat pelunasan.',
                        ]);
                    }

                    $debt->status = Debt::STATUS_LUNAS;

                    Transaction::create([
                        'category_id' => $debt->category_id,
                        'date' => $validated['payment_date'] ?? now(),
                        'amount' => $debt->amount,
                        'description' => $debt->description,
                        'user_id' => $request->user()->id, // Keamanan: Pastikan transaksi memiliki pemilik
                    ]);

                    $this->transactionService->clearSummaryCacheForUser($request->user());
                } else {
                    $debt->status = Debt::STATUS_BELUM_LUNAS;
                }

                $debt->save();
            });

            return redirect()->route('debts.index')->with('success', 'Pembayaran berhasil dicatat.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            // Jika terjadi error, tampilkan pesan kesalahan
            return back()->withErrors('Terjadi kesalahan saat menyimpan pembayaran.');
        }
    }

    public function edit(Debt $debt): View
    {
        $this->authorize('update', $debt);

        return view('debts.edit', [
            'debt' => $debt,
        ]);
    }

    public function update(Request $request, Debt $debt): RedirectResponse
    {
        $this->authorize('update', $debt);

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'related_party' => 'required|string|max:255',
            'due_date' => 'nullable|date',
        ]);

        $debt->update($validated);

        return redirect()->route('debts.index')->with('success', 'Catatan berhasil diperbarui.');
    }

    public function markAsFailed(Request $request, Debt $debt): RedirectResponse
    {
        $this->authorize('update', $debt);

        if ($debt->status !== Debt::STATUS_BELUM_LUNAS) {
            return redirect()->route('debts.index')->with('info', 'Catatan ini tidak dapat ditandai gagal.');
        }

        if (!$debt->category_id) {
            return redirect()->route('debts.index')->withErrors(['error' => 'Tidak dapat menandai gagal karena tidak ada kategori yang terhubung. Silakan lakukan pembayaran parsial terlebih dahulu untuk menyambungkan kategori.']);
        }

        $this->finalizeFailedDebt($debt, $request->user());

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

            Transaction::create([
                'category_id' => $debt->category_id,
                'date' => now(),
                'amount' => $remainingAmount,
                'description' => ($auto ? '[Otomatis] ' : '') . 'Gagal Project: ' . $debt->description,
                'user_id' => $user->id,
            ]);

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

    /**
     * Menghapus catatan hutang/piutang.
     */
    public function destroy(Debt $debt): RedirectResponse
    {
        // Keamanan: Otorisasi sudah ditangani oleh __construct()
        $debt->delete();
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
}
