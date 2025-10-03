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

            $currentDownPayment = round((float) $invoice->down_payment, 2);
            $invoiceTotal = round((float) $invoice->total, 2);
            $remainingBalance = max($invoiceTotal - $currentDownPayment, 0);

            $relatedParty = $invoice->client_name
                ?: ($invoice->client_whatsapp ?: 'Klien Invoice #' . $invoice->number);

            $debt = Debt::updateOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'description' => $invoice->itemDescriptionSummary(),
                    'related_party' => $relatedParty,
                    'type' => Debt::TYPE_DOWN_PAYMENT,
                    'amount' => $invoiceTotal,
                    'due_date' => $invoice->due_date,
                    'status' => Debt::STATUS_LUNAS,
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

            if ($remainingBalance > 0) {
                $debt->payments()->create([
                    'amount' => $remainingBalance,
                    'payment_date' => $now,
                    'notes' => 'Pelunasan invoice #' . $invoice->number . ' melalui tautan konfirmasi',
                ]);
            }

            $debt->load('payments');

            $downPaymentTotal = $debt->payments->sum('amount');

            $invoice->forceFill([
                'down_payment' => min($invoiceTotal, $downPaymentTotal),
                'payment_date' => $now,
                'status' => 'lunas',
            ])->save();

            $debt->forceFill([
                'status' => Debt::STATUS_LUNAS,
                'description' => $invoice->itemDescriptionSummary(),
                'amount' => $invoiceTotal,
                'due_date' => $invoice->due_date,
                'related_party' => $relatedParty,
            ])->save();

            if ($remainingBalance > 0) {
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
                        'amount' => $remainingBalance,
                        'description' => 'Pelunasan invoice #' . $invoice->number . ' melalui tautan konfirmasi',
                        'date' => $now,
                    ]);
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
