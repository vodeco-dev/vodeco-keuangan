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
    public function getDebts(Request $request, User $user): LengthAwarePaginator
    {
        $query = Debt::with(['payments', 'category', 'invoice'])
            ->where('user_id', $user->id)
            ->latest();

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
    public function getSummary(User $user): array
    {
        // Gunakan filter yang sama dengan getDebts() untuk konsistensi
        $totalDownPayment = Debt::where('user_id', $user->id)
            ->where('type', Debt::TYPE_DOWN_PAYMENT)
            ->where(function ($query) {
                $this->applyInvoiceFilter($query);
            })
            ->sum('amount');

        $totalPassThrough = Debt::where('user_id', $user->id)
            ->where('type', Debt::TYPE_PASS_THROUGH)
            ->where(function ($query) {
                $this->applyInvoiceFilter($query);
            })
            ->sum('amount');

        $totalBelumLunas = Debt::where('user_id', $user->id)
            ->where('status', Debt::STATUS_BELUM_LUNAS)
            ->where(function ($query) {
                $this->applyInvoiceFilter($query);
            })
            ->get()
            ->sum('remaining_amount');

        $totalLunas = Debt::where('user_id', $user->id)
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
     * Sync missing debts for invoices that should have debts.
     * Creates debt records for invoices with status "belum lunas" that don't have debt yet.
     */
    public function syncMissingDebts(): int
    {
        $count = 0;
        
        // Cari invoice yang belum lunas dan belum punya debt
        $invoices = \App\Models\Invoice::whereIn('status', ['belum lunas', 'belum bayar'])
            ->whereDoesntHave('debt')
            ->where('type', '!=', \App\Models\Invoice::TYPE_SETTLEMENT)
            ->get();

        foreach ($invoices as $invoice) {
            // Skip pass-through invoice yang masih needs_confirmation
            $isPassThrough = in_array($invoice->type, [
                \App\Models\Invoice::TYPE_PASS_THROUGH_NEW,
                \App\Models\Invoice::TYPE_PASS_THROUGH_EXISTING
            ], true);

            if ($isPassThrough && $invoice->needs_confirmation) {
                continue; // Pass-through invoice harus dikonfirmasi dulu
            }

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

                Debt::create([
                    'invoice_id' => $invoice->id,
                    'description' => $invoice->transactionDescription(),
                    'related_party' => $relatedParty,
                    'type' => Debt::TYPE_DOWN_PAYMENT,
                    'amount' => $invoice->total,
                    'due_date' => $invoice->due_date,
                    'status' => Debt::STATUS_BELUM_LUNAS,
                    'user_id' => $invoice->user_id,
                    'category_id' => $categoryId,
                ]);

                // Jika invoice sudah punya down_payment, buat payment untuk debt
                if ($invoice->down_payment > 0) {
                    $invoice->load('debt');
                    $debt = $invoice->debt;
                    if ($debt) {
                        $debt->payments()->create([
                            'amount' => $invoice->down_payment,
                            'payment_date' => $invoice->payment_date ?? now(),
                            'notes' => 'Down payment invoice #' . $invoice->number,
                        ]);
                    }
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
              ->orWhere('type', Debt::TYPE_DOWN_PAYMENT) // Down payment selalu muncul
              ->orWhereHas('invoice', function ($invoiceQuery) {
                  // Pass-through: hanya tampilkan jika needs_confirmation = false
                  $invoiceQuery->where('needs_confirmation', false);
              });
        });
    }
}
