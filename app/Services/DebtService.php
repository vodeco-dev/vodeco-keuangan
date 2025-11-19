<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DebtService
{
    /**
     * Retrieve debts with optional filters and paginate the results.
     */
    public function getDebts(Request $request, ?User $user = null): LengthAwarePaginator
    {
        // Auto-fix inconsistent debt statuses before displaying
        $this->fixInconsistentDebtStatuses();

        $query = Debt::with(['payments', 'category', 'invoice']);

        // If user is provided, filter by that user, otherwise get all users' data
        if ($user) {
            $query->where('user_id', $user->id);
        }
        
        // Sorting: default terbaru ke terlama berdasarkan created_at
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // Validasi sort_by untuk keamanan
        $allowedSortColumns = ['created_at', 'updated_at', 'due_date', 'amount'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        
        // Validasi sort_order
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';
        
        $query->orderBy($sortBy, $sortOrder);

        // Filter out debts where related invoice still needs confirmation
        // Ini memastikan invoice iklan (pass-through) melewati konfirmasi pembayaran sebelum muncul di halaman hutang
        // Untuk down payment, debt tetap muncul meskipun invoice masih needs_confirmation = true
        $this->applyInvoiceFilter($query);

        if ($request->filled('type_filter')) {
            $query->where('type', $request->type_filter);
        }

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('related_party', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('due_date_from')) {
            $query->whereDate('due_date', '>=', $request->due_date_from);
        }

        if ($request->filled('due_date_to')) {
            $query->whereDate('due_date', '<=', $request->due_date_to);
        }

        return $query->paginate();
    }

    /**
     * Calculate summary amounts for debts.
     */
    public function getSummary(?User $user = null): array
    {
        // Gunakan filter yang sama dengan getDebts() untuk konsistensi
        $baseQuery = Debt::when($user, function ($query) use ($user) {
            return $query->where('user_id', $user->id);
        });

        $totalDownPayment = (clone $baseQuery)
            ->where('type', Debt::TYPE_DOWN_PAYMENT)
            ->where(function ($query) {
                $this->applyInvoiceFilter($query);
            })
            ->sum('amount');

        $totalPassThrough = (clone $baseQuery)
            ->where('type', Debt::TYPE_PASS_THROUGH)
            ->where(function ($query) {
                $this->applyInvoiceFilter($query);
            })
            ->sum('amount');

        $totalBelumLunas = (clone $baseQuery)
            ->where('status', Debt::STATUS_BELUM_LUNAS)
            ->where(function ($query) {
                $this->applyInvoiceFilter($query);
            })
            ->get()
            ->sum('remaining_amount');

        $totalLunas = (clone $baseQuery)
            ->where('status', Debt::STATUS_LUNAS)
            ->where(function ($query) {
                $this->applyInvoiceFilter($query);
            })
            ->sum('amount');

        return [
            'totalPassThrough' => $totalPassThrough,
            'totalDownPayment' => $totalDownPayment,
            'totalBelumLunas' => $totalBelumLunas,
            'totalLunas' => $totalLunas,
        ];
    }

    /**
     * Sync completed debts to transactions.
     * Creates transaction records for debts that are 100% complete but don't have transactions yet.
     */
    public function syncCompletedDebtsToTransactions(): int
    {
        $count = 0;

        // Cari debt yang sudah lunas (progress 100%) tapi belum ada transaksinya
        $completedDebts = Debt::where('status', Debt::STATUS_LUNAS)
            ->whereNotNull('category_id') // Harus ada kategori untuk membuat transaksi
            ->get()
            ->filter(function ($debt) {
                // Filter berdasarkan paid_amount >= amount menggunakan accessor
                return $debt->paid_amount >= $debt->amount;
            });

        foreach ($completedDebts as $debt) {
            // Cek apakah sudah ada transaksi dengan deskripsi serupa
            $description = $debt->description;
            if ($debt->invoice_id) {
                $debt->loadMissing('invoice');
                if ($debt->invoice) {
                    $invoiceNumber = '(' . $debt->invoice->number . ')';
                    if (strpos($description, $invoiceNumber) === false) {
                        $description = $description . ' ' . $invoiceNumber;
                    }
                }
            }

            $existingTransaction = \App\Models\Transaction::where('description', $description)
                ->where('user_id', $debt->user_id)
                ->where('amount', $debt->amount)
                ->where('category_id', $debt->category_id)
                ->first();

            if (!$existingTransaction) {
                \App\Models\Transaction::create([
                    'category_id' => $debt->category_id,
                    'date' => now(), // Gunakan tanggal sekarang jika tidak ada info tanggal pelunasan
                    'amount' => $debt->amount,
                    'description' => $description,
                    'user_id' => $debt->user_id,
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Fix debt statuses that are inconsistent with their payment progress.
     * Updates status to 'lunas' for debts that are 100% paid but still marked as 'belum lunas'.
     */
    public function fixInconsistentDebtStatuses(): int
    {
        $count = 0;

        // Cari debt yang paid_amount >= amount tapi status masih 'belum lunas'
        $inconsistentDebts = Debt::where('status', Debt::STATUS_BELUM_LUNAS)
            ->get()
            ->filter(function ($debt) {
                return $debt->paid_amount >= $debt->amount;
            });

        foreach ($inconsistentDebts as $debt) {
            $debt->update(['status' => Debt::STATUS_LUNAS]);
            $count++;
        }

        return $count;
    }

    /**
     * Sync missing debts for invoices that should have debts.
     * Creates debt records for invoices that don't have debt yet.
     * Only includes invoices that are already confirmed (needs_confirmation = false).
     * Invoice yang belum dikonfirmasi tidak boleh masuk ke hutang atau transaksi.
     */
    public function syncMissingDebts(): int
    {
        $count = 0;

        // Cari invoice yang belum punya debt DAN sudah dikonfirmasi
        // Invoice yang belum dikonfirmasi (needs_confirmation = true) tidak boleh masuk ke hutang/transaksi
        $invoices = \App\Models\Invoice::whereDoesntHave('debt')
            ->where('type', '!=', \App\Models\Invoice::TYPE_SETTLEMENT)
            ->where('needs_confirmation', false) // Hanya invoice yang sudah dikonfirmasi
            ->get();

        foreach ($invoices as $invoice) {
            // Semua invoice di sini sudah dikonfirmasi (needs_confirmation = false)
            $isPassThrough = in_array($invoice->type, [
                \App\Models\Invoice::TYPE_PASS_THROUGH_NEW,
                \App\Models\Invoice::TYPE_PASS_THROUGH_EXISTING
            ], true);

            $relatedParty = $invoice->client_name
                ?: ($invoice->client_whatsapp ?: 'Klien Invoice #' . $invoice->number);

            if ($isPassThrough) {
                // Untuk pass-through invoice, cari item "Dana Invoices Iklan"
                $adBudgetItem = $invoice->items->first(function ($item) {
                    return strpos($item->description, 'Dana Invoices Iklan') !== false;
                });

                if (!$adBudgetItem) {
                    continue; // Skip jika tidak ada item dana iklan
                }

                $adBudgetTotal = round($adBudgetItem->price * $adBudgetItem->quantity, 2);
                
                // Parse durationDays dari description
                $description = $adBudgetItem->description;
                $durationDays = 1;
                if (preg_match('/(\d+)\s*hari/i', $description, $matches)) {
                    $durationDays = max(1, (int) $matches[1]);
                }
                
                $dailyBalanceTotal = $durationDays > 0 
                    ? round($adBudgetTotal / $durationDays, 2) 
                    : 0;

                Debt::create([
                    'invoice_id' => $invoice->id,
                    'description' => $invoice->transactionDescription(),
                    'related_party' => $relatedParty,
                    'type' => Debt::TYPE_PASS_THROUGH,
                    'amount' => $adBudgetTotal,
                    'due_date' => $invoice->due_date,
                    'status' => Debt::STATUS_BELUM_LUNAS,
                    'user_id' => $invoice->user_id,
                    'daily_deduction' => $dailyBalanceTotal,
                ]);
            } else {
                // Untuk invoice biasa (down payment)
                $firstItem = $invoice->items()->first();
                $categoryId = $firstItem?->category_id;
                
                // Tentukan status debt berdasarkan status invoice
                // Jika invoice sudah dikonfirmasi dan statusnya 'lunas', debt juga harus 'lunas'
                $debtStatus = ($invoice->needs_confirmation === false && $invoice->status === 'lunas')
                    ? Debt::STATUS_LUNAS
                    : Debt::STATUS_BELUM_LUNAS;

                $debt = Debt::create([
                    'invoice_id' => $invoice->id,
                    'description' => $invoice->transactionDescription(),
                    'related_party' => $relatedParty,
                    'type' => Debt::TYPE_DOWN_PAYMENT,
                    'amount' => $invoice->total,
                    'due_date' => $invoice->due_date,
                    'status' => $debtStatus,
                    'user_id' => $invoice->user_id,
                    'category_id' => $categoryId,
                ]);

                // Jika invoice sudah punya down_payment, buat payment untuk debt
                if ($invoice->down_payment > 0) {
                    $debt->payments()->create([
                        'amount' => $invoice->down_payment,
                        'payment_date' => $invoice->payment_date ?? now(),
                        'notes' => 'Down payment invoice #' . $invoice->number,
                    ]);
                }
            }

            $count++;
        }

        return $count;
    }

    /**
     * Apply invoice filter to query (helper method untuk konsistensi).
     */
    private function applyInvoiceFilter($query): void
    {
        $query->where(function ($q) {
            $q->whereNull('invoice_id') // Debt tanpa invoice selalu muncul
              ->orWhere(function ($downPaymentQuery) {
                  // Down payment hanya muncul jika invoice sudah dikonfirmasi
                  $downPaymentQuery->where('type', Debt::TYPE_DOWN_PAYMENT)
                    ->whereHas('invoice', function ($invoiceQuery) {
                        $invoiceQuery->where('needs_confirmation', false);
                    });
              })
              ->orWhereHas('invoice', function ($invoiceQuery) {
                  // Pass-through: hanya tampilkan jika needs_confirmation = false
                  $invoiceQuery->where('needs_confirmation', false);
              });
        });
    }
}
