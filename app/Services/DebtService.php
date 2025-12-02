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

        return [
            'totalPassThrough' => $totalPassThrough,
            'totalDownPayment' => $totalDownPayment,
            'totalBelumLunas' => $totalBelumLunas,
            'totalLunas' => $totalLunas,
        ];
    }

    public function syncCompletedDebtsToTransactions(): int
    {
        $count = 0;

        $completedDebts = Debt::where('status', Debt::STATUS_LUNAS)
            ->whereNotNull('category_id')
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

            $description = $debt->description;
            if ($debt->invoice_id && $debt->invoice) {
                $invoiceNumber = '(' . $debt->invoice->number . ')';
                if (strpos($description, $invoiceNumber) === false) {
                    $description = $description . ' ' . $invoiceNumber;
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
