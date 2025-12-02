<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Debt;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceSettlementService
{
    public function refreshToken(Invoice $invoice, ?string $expiresAt = null): Invoice
    {
        $expiry = $expiresAt
            ? Carbon::parse($expiresAt)
            : now()->addDays(7);

        $invoice->forceFill([
            'settlement_token' => Str::random(64),
            'settlement_token_expires_at' => $expiry,
        ])->save();

        return $invoice->refresh();
    }

    public function revokeToken(Invoice $invoice): void
    {
        $invoice->forceFill([
            'settlement_token' => null,
            'settlement_token_expires_at' => null,
        ])->save();
    }

    public function confirmSettlement(Invoice $invoice, string $ipAddress, string $url): void
    {
        DB::transaction(function () use ($invoice, $ipAddress, $url): void {
            $now = now();

            $invoice->loadMissing('items', 'debt.payments');

            $wasNeedingConfirmation = $invoice->needs_confirmation;

            $currentDownPayment = round((float) $invoice->down_payment, 2);
            $invoiceTotal = round((float) $invoice->total, 2);
            $remainingBalance = max($invoiceTotal - $currentDownPayment, 0);

            $relatedParty = $invoice->client_name
                ?: ($invoice->client_whatsapp ?: 'Klien Invoice #' . $invoice->number);

            $isPassThrough = in_array($invoice->type, [
                Invoice::TYPE_PASS_THROUGH_NEW,
                Invoice::TYPE_PASS_THROUGH_EXISTING
            ], true);

            $adBudgetTotal = null;
            $dailyBalanceTotal = null;

            if ($isPassThrough) {
                $adBudgetItem = $invoice->items->first(function ($item) {
                    return strpos($item->description, 'Dana Invoices Iklan') !== false;
                });

                if (!$adBudgetItem) {
                    $adBudgetTotal = $invoiceTotal;
                    $dailyBalanceTotal = 0;
                } else {
                    $adBudgetTotal = round($adBudgetItem->price * $adBudgetItem->quantity, 2);
                    
                    $description = $adBudgetItem->description;
                    $durationDays = 1;
                    if (preg_match('/(\d+)\s*hari/i', $description, $matches)) {
                        $durationDays = max(1, (int) $matches[1]);
                    }
                    
                    $dailyBalanceTotal = $durationDays > 0 
                        ? round($adBudgetTotal / $durationDays, 2) 
                        : 0;
                }

                $debt = Debt::updateOrCreate(
                    ['invoice_id' => $invoice->id],
                    [
                        'description' => $invoice->transactionDescription(),
                        'related_party' => $relatedParty,
                        'type' => Debt::TYPE_PASS_THROUGH,
                        'amount' => $adBudgetTotal,
                        'due_date' => $invoice->due_date,
                        'status' => Debt::STATUS_BELUM_LUNAS,
                        'user_id' => $invoice->user_id,
                        'daily_deduction' => $dailyBalanceTotal,
                    ]
                );
            } else {
                $debt = Debt::updateOrCreate(
                    ['invoice_id' => $invoice->id],
                    [
                        'description' => $invoice->transactionDescription(),
                        'related_party' => $relatedParty,
                        'type' => Debt::TYPE_DOWN_PAYMENT,
                        'amount' => $invoiceTotal,
                        'due_date' => $invoice->due_date,
                        'status' => Debt::STATUS_BELUM_LUNAS,
                        'user_id' => $invoice->user_id,
                    ]
                );

                if ($debt->wasRecentlyCreated && ! $debt->category_id) {
                    $firstItem = $invoice->items()->first();
                    if ($firstItem && $firstItem->category_id) {
                        $debt->category_id = $firstItem->category_id;
                        $debt->save();
                    }
                }
            }

            if (!$isPassThrough) {
                $debt->load('payments');
                $existingPaymentsTotal = $debt->payments->sum('amount');
                $totalPaymentNeeded = $currentDownPayment + $remainingBalance;
                $paymentNeeded = max(0, $totalPaymentNeeded - $existingPaymentsTotal);
                
                if ($paymentNeeded > 0) {
                    $debt->payments()->create([
                        'amount' => $paymentNeeded,
                        'payment_date' => $now,
                        'notes' => $remainingBalance > 0 
                            ? 'Pelunasan invoice #' . $invoice->number . ' melalui tautan konfirmasi'
                            : 'Konfirmasi down payment invoice #' . $invoice->number . ' melalui tautan konfirmasi',
                    ]);
                }
            }

            if ($isPassThrough) {
                $invoice->forceFill([
                    'down_payment' => $invoiceTotal,
                    'payment_date' => $now,
                    'status' => 'lunas',
                    'needs_confirmation' => false,
                ])->save();
            } else {
                $debt->load('payments');
                $downPaymentTotal = $debt->payments->sum('amount');
                
                if ($downPaymentTotal >= $invoiceTotal && $invoiceTotal > 0) {
                    $invoiceStatus = 'lunas';
                } elseif ($downPaymentTotal > 0) {
                    $invoiceStatus = 'belum lunas';
                } else {
                    $invoiceStatus = 'belum bayar';
                }
                
                $invoice->forceFill([
                    'down_payment' => min($invoiceTotal, $downPaymentTotal),
                    'payment_date' => $now,
                    'status' => $invoiceStatus,
                    'needs_confirmation' => false,
                ])->save();
            }

            if ($isPassThrough) {
                $debtAmount = isset($adBudgetTotal) ? $adBudgetTotal : $invoiceTotal;
                $debtStatus = Debt::STATUS_BELUM_LUNAS;
            } else {
                $debtAmount = $invoiceTotal;
                $debt->load('payments');
                $paidAmount = $debt->payments->sum('amount');
                $debtStatus = ($paidAmount >= $debtAmount && $debtAmount > 0) 
                    ? Debt::STATUS_LUNAS 
                    : Debt::STATUS_BELUM_LUNAS;
            }
            
            $debt->forceFill([
                'status' => $debtStatus,
                'description' => $invoice->transactionDescription(),
                'amount' => $debtAmount,
                'due_date' => $invoice->due_date,
                'related_party' => $relatedParty,
            ])->save();

            if ($isPassThrough && $wasNeedingConfirmation) {
                $incomeCategoryId = $invoice->items->first()?->category_id;
                if ($incomeCategoryId) {
                    $firstItem = $invoice->items->first();
                    $quantity = $firstItem ? max(1, (int) $firstItem->quantity) : 1;
                    
                    $clientInfo = $invoice->client_name ?: $invoice->client_whatsapp ?: 'Klien';
                    $description = 'Invoices Iklan' . ($quantity > 1 ? ' (x' . $quantity . ')' : '') . ' - ' . $clientInfo . ' (' . $invoice->number . ')';
                    
                    Transaction::create([
                        'category_id' => $incomeCategoryId,
                        'user_id' => $invoice->user_id,
                        'amount' => $invoiceTotal,
                        'description' => $description,
                        'date' => $now,
                    ]);
                }
            } elseif (!$isPassThrough) {
                $invoice->refresh();
                if ($invoice->status === 'lunas') {
                    $categoryId = $debt->category_id;

                    if (! $categoryId) {
                        $firstItem = $invoice->items()->first();
                        if ($firstItem && $firstItem->category_id) {
                            $categoryId = $firstItem->category_id;
                            $debt->category_id = $categoryId;
                            $debt->save();
                        }
                    }

                    if ($categoryId) {
                        Transaction::create([
                            'category_id' => $categoryId,
                            'user_id' => $invoice->user_id,
                            'amount' => $invoiceTotal,
                            'description' => $invoice->transactionDescription(),
                            'date' => $now,
                        ]);
                    }
                }
            }

            ActivityLog::create([
                'user_id' => $invoice->user_id,
                'description' => 'Konfirmasi pelunasan invoice #' . $invoice->number . ' melalui tautan publik',
                'method' => 'POST',
                'url' => $url,
                'ip_address' => $ipAddress,
            ]);
        });
    }
}
