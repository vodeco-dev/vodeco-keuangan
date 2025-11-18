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

            // Simpan status needs_confirmation sebelum diubah
            $wasNeedingConfirmation = $invoice->needs_confirmation;

            $currentDownPayment = round((float) $invoice->down_payment, 2);
            $invoiceTotal = round((float) $invoice->total, 2);
            $remainingBalance = max($invoiceTotal - $currentDownPayment, 0);

            $relatedParty = $invoice->client_name
                ?: ($invoice->client_whatsapp ?: 'Klien Invoice #' . $invoice->number);

            // Cek apakah invoice adalah pass-through type
            $isPassThrough = in_array($invoice->type, [
                Invoice::TYPE_PASS_THROUGH_NEW,
                Invoice::TYPE_PASS_THROUGH_EXISTING
            ], true);

            $adBudgetTotal = null;
            $dailyBalanceTotal = null;

            if ($isPassThrough) {
                // Untuk pass-through invoice, cari item "Dana Invoices Iklan" untuk mendapatkan adBudgetTotal
                $adBudgetItem = $invoice->items->first(function ($item) {
                    return strpos($item->description, 'Dana Invoices Iklan') !== false;
                });

                if (!$adBudgetItem) {
                    // Fallback: gunakan total invoice jika item tidak ditemukan
                    $adBudgetTotal = $invoiceTotal;
                    $dailyBalanceTotal = 0;
                } else {
                    // adBudgetTotal = price * quantity dari item "Dana Invoices Iklan"
                    $adBudgetTotal = round($adBudgetItem->price * $adBudgetItem->quantity, 2);
                    
                    // Parse durationDays dari description untuk menghitung dailyBalanceTotal
                    $description = $adBudgetItem->description;
                    $durationDays = 1;
                    if (preg_match('/(\d+)\s*hari/i', $description, $matches)) {
                        $durationDays = max(1, (int) $matches[1]);
                    }
                    
                    $dailyBalanceTotal = $durationDays > 0 
                        ? round($adBudgetTotal / $durationDays, 2) 
                        : 0;
                }

                // Buat Debt untuk pass-through invoice
                // Catatan: Debt untuk pass-through invoice mencatat Saldo Harian × Durasi (adBudgetTotal)
                // Debt ini akan dicatat sebagai pengeluaran nanti ketika dana iklan digunakan
                // Debt status = BELUM_LUNAS karena paid_amount masih 0 (belum ada penggunaan dana iklan)
                // Debt tidak menggunakan kategori dari invoice items karena invoice items menggunakan kategori pemasukan
                // Kategori akan di-set saat pembayaran/penggunaan debt dilakukan (kategori pengeluaran)
                $debt = Debt::updateOrCreate(
                    ['invoice_id' => $invoice->id],
                    [
                        'description' => $invoice->transactionDescription(),
                        'related_party' => $relatedParty,
                        'type' => Debt::TYPE_PASS_THROUGH,
                        'amount' => $adBudgetTotal, // Amount = Saldo Harian × Durasi (hanya dana iklan)
                        'due_date' => $invoice->due_date,
                        'status' => Debt::STATUS_BELUM_LUNAS, // Status belum lunas karena paid_amount masih 0
                        'user_id' => $invoice->user_id,
                        'daily_deduction' => $dailyBalanceTotal,
                        // Jangan set category_id dari invoice items karena invoice items menggunakan kategori pemasukan
                        // Debt untuk pass-through invoice harus menggunakan kategori pengeluaran
                    ]
                );
            } else {
                // Untuk invoice biasa (down payment), gunakan logika yang sudah ada
                // Status debt akan di-update setelah payment dibuat dan dihitung
                $debt = Debt::updateOrCreate(
                    ['invoice_id' => $invoice->id],
                    [
                        'description' => $invoice->transactionDescription(),
                        'related_party' => $relatedParty,
                        'type' => Debt::TYPE_DOWN_PAYMENT,
                        'amount' => $invoiceTotal, // Total hutang = invoice total
                        'due_date' => $invoice->due_date,
                        'status' => Debt::STATUS_BELUM_LUNAS, // Status awal belum lunas, akan di-update setelah payment
                        'user_id' => $invoice->user_id,
                    ]
                );

                // Untuk invoice biasa, set kategori dari invoice items jika debt baru dibuat
                if ($debt->wasRecentlyCreated && ! $debt->category_id) {
                    $firstItem = $invoice->items()->first();
                    if ($firstItem && $firstItem->category_id) {
                        $debt->category_id = $firstItem->category_id;
                        $debt->save();
                    }
                }
            }

            // Untuk pass-through invoice, tidak perlu membuat payment ke debt saat konfirmasi
            // karena debt mencatat dana iklan yang akan digunakan nanti (belum digunakan, paid_amount = 0)
            // Payment ke debt akan dibuat nanti ketika dana iklan digunakan (dicatat sebagai pengeluaran)
            // Untuk invoice biasa, buat payment ke debt seperti biasa
            // Jika invoice sudah punya down_payment, kita perlu membuat payment untuk down_payment yang sudah ada juga
            if (!$isPassThrough) {
                $debt->load('payments');
                $existingPaymentsTotal = $debt->payments->sum('amount');
                
                // Jika sudah ada down_payment di invoice, pastikan payment dibuat untuk down_payment tersebut
                // Total payment yang seharusnya ada = currentDownPayment + remainingBalance
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

            // Untuk pass-through invoice, langsung set invoice sebagai lunas karena dana sudah masuk
            // Untuk invoice biasa, hitung down_payment dari payments dan tentukan status berdasarkan jumlah yang sudah dibayar
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
                
                // Tentukan status invoice berdasarkan down_payment vs total
                // Untuk down payment, status tidak langsung lunas, harus berdasarkan jumlah yang sudah dibayar
                if ($downPaymentTotal >= $invoiceTotal && $invoiceTotal > 0) {
                    $invoiceStatus = 'lunas';
                } elseif ($downPaymentTotal > 0) {
                    $invoiceStatus = 'belum lunas';
                } else {
                    $invoiceStatus = 'belum bayar';
                }
                
                $invoice->forceFill([
                    'down_payment' => min($invoiceTotal, $downPaymentTotal), // Jumlah yang sudah dibayar = down_payment
                    'payment_date' => $now,
                    'status' => $invoiceStatus,
                    'needs_confirmation' => false,
                ])->save();
            }

            // Untuk pass-through invoice, amount = adBudgetTotal (Saldo Harian × Durasi)
            // Status tetap BELUM_LUNAS karena paid_amount masih 0 (belum ada penggunaan dana iklan)
            // Untuk invoice biasa, update amount sesuai invoice total dan status berdasarkan paid_amount
            if ($isPassThrough) {
                $debtAmount = isset($adBudgetTotal) ? $adBudgetTotal : $invoiceTotal;
                $debtStatus = Debt::STATUS_BELUM_LUNAS;
            } else {
                // Untuk down payment invoice, total hutang = invoice total
                $debtAmount = $invoiceTotal;
                // Status debt ditentukan berdasarkan paid_amount vs amount
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

            // Untuk pass-through invoice, catat transaksi pemasukan saat invoice dikonfirmasi pertama kali
            // (saat needs_confirmation berubah dari true ke false)
            if ($isPassThrough && $wasNeedingConfirmation) {
                $incomeCategoryId = $invoice->items->first()?->category_id;
                if ($incomeCategoryId) {
                    // Ambil quantity dari item pertama (semua items menggunakan quantity yang sama)
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
                // Untuk invoice biasa (down payment), catat transaksi hanya saat invoice benar-benar lunas
                // Transaksi dicatat dengan jumlah = invoice total (bukan hanya remaining balance)
                $invoice->refresh(); // Reload untuk mendapatkan status terbaru
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
                            'amount' => $invoiceTotal, // Catat dengan jumlah invoice total saat lunas
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
