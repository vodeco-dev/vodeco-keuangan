<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DebtService
{
    public function getDebts(Request $request, ?User $user = null): LengthAwarePaginator
    {
        $this->fixInconsistentDebtStatuses();

        $query = Debt::with(['payments', 'category', 'invoice']);

        if ($user) {
            $query->where('user_id', $user->id);
        }
        
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $allowedSortColumns = ['created_at', 'updated_at', 'due_date', 'amount'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';
        
        $query->orderBy($sortBy, $sortOrder);

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

    public function getSummary(?User $user = null): array
    {
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

        // Breakdown Belum Lunas by type
        $belumLunasDownPayment = (clone $baseQuery)
            ->where('status', Debt::STATUS_BELUM_LUNAS)
            ->where('type', Debt::TYPE_DOWN_PAYMENT)
            ->where(function ($query) {
                $this->applyInvoiceFilter($query);
            })
            ->get()
            ->sum('remaining_amount');

        $belumLunasPassThrough = (clone $baseQuery)
            ->where('status', Debt::STATUS_BELUM_LUNAS)
            ->where('type', Debt::TYPE_PASS_THROUGH)
            ->where(function ($query) {
                $this->applyInvoiceFilter($query);
            })
            ->get()
            ->sum('remaining_amount');

        // Breakdown Lunas by type
        $lunasDownPayment = (clone $baseQuery)
            ->where('status', Debt::STATUS_LUNAS)
            ->where('type', Debt::TYPE_DOWN_PAYMENT)
            ->where(function ($query) {
                $this->applyInvoiceFilter($query);
            })
            ->sum('amount');

        $lunasPassThrough = (clone $baseQuery)
            ->where('status', Debt::STATUS_LUNAS)
            ->where('type', Debt::TYPE_PASS_THROUGH)
            ->where(function ($query) {
                $this->applyInvoiceFilter($query);
            })
            ->sum('amount');

        // Breakdown Lunas by payment status (full vs partial)
        $lunasDebts = (clone $baseQuery)
            ->where('status', Debt::STATUS_LUNAS)
            ->where(function ($query) {
                $this->applyInvoiceFilter($query);
            })
            ->with('payments')
            ->get();

        $lunasFullAmount = 0;
        $lunasPartialAmount = 0;
        $lunasFullCount = 0;
        $lunasPartialCount = 0;

        foreach ($lunasDebts as $debt) {
            if ($debt->paid_amount >= $debt->amount) {
                $lunasFullAmount += $debt->amount;
                $lunasFullCount++;
            } else {
                $lunasPartialAmount += $debt->amount;
                $lunasPartialCount++;
            }
        }

        // Breakdown Belum Lunas by payment status (sudah cicilan vs belum ada cicilan)
        $belumLunasDebts = (clone $baseQuery)
            ->where('status', Debt::STATUS_BELUM_LUNAS)
            ->where(function ($query) {
                $this->applyInvoiceFilter($query);
            })
            ->with('payments')
            ->get();

        $belumLunasSudahCicilanAmount = 0;
        $belumLunasBelumCicilanAmount = 0;
        $belumLunasSudahCicilanCount = 0;
        $belumLunasBelumCicilanCount = 0;

        foreach ($belumLunasDebts as $debt) {
            if ($debt->paid_amount > 0) {
                $belumLunasSudahCicilanAmount += $debt->remaining_amount;
                $belumLunasSudahCicilanCount++;
            } else {
                $belumLunasBelumCicilanAmount += $debt->remaining_amount;
                $belumLunasBelumCicilanCount++;
            }
        }

        return [
            'totalPassThrough' => $totalPassThrough,
            'totalDownPayment' => $totalDownPayment,
            'totalBelumLunas' => $totalBelumLunas,
            'totalLunas' => $totalLunas,
            'belumLunasDownPayment' => $belumLunasDownPayment,
            'belumLunasPassThrough' => $belumLunasPassThrough,
            'lunasDownPayment' => $lunasDownPayment,
            'lunasPassThrough' => $lunasPassThrough,
            // Lunas breakdown by payment completion
            'lunasFullAmount' => $lunasFullAmount,
            'lunasPartialAmount' => $lunasPartialAmount,
            'lunasFullCount' => $lunasFullCount,
            'lunasPartialCount' => $lunasPartialCount,
            // Belum Lunas breakdown by payment progress
            'belumLunasSudahCicilanAmount' => $belumLunasSudahCicilanAmount,
            'belumLunasBelumCicilanAmount' => $belumLunasBelumCicilanAmount,
            'belumLunasSudahCicilanCount' => $belumLunasSudahCicilanCount,
            'belumLunasBelumCicilanCount' => $belumLunasBelumCicilanCount,
        ];
    }

    public function syncCompletedDebtsToTransactions(): int
    {
        $count = 0;

        $completedDebts = Debt::where('status', Debt::STATUS_LUNAS)
            ->whereNotNull('category_id')
            ->with('payments') // Eager load payments to check for existing transactions
            ->get()
            ->filter(function ($debt) {
                return $debt->paid_amount >= $debt->amount;
            });

        foreach ($completedDebts as $debt) {
            $canEnterTransaction = true;
            if ($debt->invoice_id) {
                $debt->loadMissing('invoice');
                if ($debt->invoice) {
                    $canEnterTransaction = $debt->invoice->canEnterTransactionWhenPaid();
                }
            }

            if (!$canEnterTransaction) {
                continue;
            }

            // Check if all payments already have transactions linked
            // If yes, skip creating a new transaction to prevent duplication
            $paymentsWithTransactions = $debt->payments->filter(function ($payment) {
                return $payment->transaction_id !== null;
            });

            // If all payments have transactions, the debt is already fully recorded
            // No need to create another transaction (would be duplicate)
            if ($debt->payments->isNotEmpty() && $paymentsWithTransactions->count() === $debt->payments->count()) {
                continue; // Skip this debt, already recorded via installment transactions
            }

            $description = $debt->description;
            if ($debt->invoice_id && $debt->invoice) {
                $invoiceNumber = '(' . $debt->invoice->number . ')';
                if (strpos($description, $invoiceNumber) === false) {
                    $description = $description . ' ' . $invoiceNumber;
                }
            }

            // Check if a transaction for the total amount already exists
            $existingTransaction = \App\Models\Transaction::where('description', $description)
                ->where('user_id', $debt->user_id)
                ->where('amount', $debt->amount)
                ->where('category_id', $debt->category_id)
                ->first();

            if (!$existingTransaction) {
                \App\Models\Transaction::create([
                    'category_id' => $debt->category_id,
                    'date' => now(),
                    'amount' => $debt->amount,
                    'description' => $description,
                    'user_id' => $debt->user_id,
                ]);

                $count++;
            }
        }

        return $count;
    }

    public function fixInconsistentDebtStatuses(): int
    {
        $count = 0;

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

    public function syncMissingDebts(): int
    {
        $count = 0;

        $invoices = \App\Models\Invoice::whereDoesntHave('debt')
            ->where('type', '!=', \App\Models\Invoice::TYPE_SETTLEMENT)
            ->get()
            ->filter(function ($invoice) {
                return $invoice->canEnterDebt();
            });

        foreach ($invoices as $invoice) {
            $isPassThrough = in_array($invoice->type, [
                \App\Models\Invoice::TYPE_PASS_THROUGH_NEW,
                \App\Models\Invoice::TYPE_PASS_THROUGH_EXISTING
            ], true);

            $relatedParty = $invoice->client_name
                ?: ($invoice->client_whatsapp ?: 'Klien Invoice #' . $invoice->number);

            if ($isPassThrough) {
                $adBudgetItem = $invoice->items->first(function ($item) {
                    return strpos($item->description, 'Dana Invoices Iklan') !== false;
                });

                if (!$adBudgetItem) {
                    continue;
                }

                $adBudgetTotal = round($adBudgetItem->price * $adBudgetItem->quantity, 2);
                
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
                $firstItem = $invoice->items()->first();
                $categoryId = $firstItem?->category_id;
                
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

    public function backfillPaymentTransactions(): array
    {
        $created = 0;
        $linked = 0;
        $skipped = 0;

        // Ambil semua payments yang belum punya transaction_id
        $paymentsWithoutTransaction = \App\Models\Payment::whereNull('transaction_id')
            ->with(['debt.invoice', 'debt.category', 'debt.user'])
            ->get();

        foreach ($paymentsWithoutTransaction as $payment) {
            $debt = $payment->debt;

            // Skip jika debt tidak ada atau tidak punya kategori
            if (!$debt || !$debt->category_id) {
                $skipped++;
                continue;
            }

            // Cek apakah boleh masuk transaksi
            $canEnterTransaction = true;
            $isPassThroughDebt = $debt->type === Debt::TYPE_PASS_THROUGH;
            $isDownPaymentDebt = $debt->type === Debt::TYPE_DOWN_PAYMENT;

            if ($debt->invoice_id) {
                $debt->loadMissing('invoice');
                if ($debt->invoice) {
                    $canEnterTransaction = $debt->invoice->canEnterTransactionWhenPaid();
                }
            }

            if (!$isPassThroughDebt && !$canEnterTransaction) {
                $skipped++;
                continue;
            }

            // Buat deskripsi transaksi
            $description = 'Pembayaran: ' . $debt->description;
            if ($debt->invoice_id && $debt->invoice) {
                $invoiceNumber = '(' . $debt->invoice->number . ')';
                if (strpos($description, $invoiceNumber) === false) {
                    $description = $description . ' ' . $invoiceNumber;
                }
            }

            // Tentukan kategori transaksi
            $transactionCategoryId = $debt->category_id;
            
            if ($isPassThroughDebt) {
                // Pass through (iklan) goes to expense (pengeluaran)
                $expenseCategory = \App\Models\Category::where('type', 'pengeluaran')
                    ->where('name', 'like', '%iklan%')
                    ->first();

                if (!$expenseCategory) {
                    $expenseCategory = \App\Models\Category::where('type', 'pengeluaran')
                        ->where('name', 'like', '%pengeluaran%')
                        ->first();
                }

                if ($expenseCategory) {
                    $transactionCategoryId = $expenseCategory->id;
                }
            } elseif ($isDownPaymentDebt) {
                // Down payment goes to income (pemasukan)
                $debtCategory = \App\Models\Category::find($debt->category_id);
                if ($debtCategory && $debtCategory->type !== 'pemasukan') {
                    $incomeCategory = \App\Models\Category::where('type', 'pemasukan')
                        ->where('name', 'like', '%down%payment%')
                        ->first();

                    if (!$incomeCategory) {
                        $incomeCategory = \App\Models\Category::where('type', 'pemasukan')
                            ->where('name', 'like', '%pemasukan%')
                            ->first();
                    }

                    if ($incomeCategory) {
                        $transactionCategoryId = $incomeCategory->id;
                    }
                }
            }

            // Cek apakah transaksi dengan spesifikasi yang sama sudah ada
            $existingTransaction = \App\Models\Transaction::where('description', $description)
                ->where('user_id', $debt->user_id)
                ->where('amount', $payment->amount)
                ->where('date', $payment->payment_date)
                ->where('category_id', $transactionCategoryId)
                ->first();

            if ($existingTransaction) {
                // Jika sudah ada, link saja
                $payment->update(['transaction_id' => $existingTransaction->id]);
                $linked++;
            } else {
                // Buat transaksi baru
                $transaction = \App\Models\Transaction::create([
                    'category_id' => $transactionCategoryId,
                    'date' => $payment->payment_date,
                    'amount' => $payment->amount,
                    'description' => $description,
                    'user_id' => $debt->user_id,
                ]);

                // Link payment dengan transaction
                $payment->update(['transaction_id' => $transaction->id]);
                $created++;
            }
        }

        return [
            'created' => $created,
            'linked' => $linked,
            'skipped' => $skipped,
            'total' => $paymentsWithoutTransaction->count(),
        ];
    }

    private function applyInvoiceFilter($query): void
    {
        $query->where(function ($q) {
            $q->whereNull('invoice_id')
            ->orWhereHas('invoice', function ($invoiceQuery) {
                $invoiceQuery->where('needs_confirmation', false);
            });
        });
    }
}
