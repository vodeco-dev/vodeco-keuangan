<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\PassThroughPackage;
use Illuminate\Support\Facades\DB;

class PassThroughInvoiceCreator
{
    /**
     * Membuat invoice pass through beserta catatan hutang terkait berdasarkan paket yang dipilih.
     */
    public function create(PassThroughPackage $package, array $attributes): Invoice
    {
        return DB::transaction(function () use ($package, $attributes) {
            $ownerId = $attributes['owner_id'] ?? null;
            $createdBy = $attributes['created_by'] ?? null;
            $customerServiceId = $attributes['customer_service_id'] ?? null;
            $customerServiceName = $attributes['customer_service_name'] ?? null;
            $clientName = $attributes['client_name'] ?? null;
            $clientWhatsapp = $attributes['client_whatsapp'] ?? null;
            $clientAddress = $attributes['client_address'] ?? null;
            $dueDate = $attributes['due_date'] ?? null;
            $issueDate = $attributes['issue_date'] ?? now();

            $remaining = $package->remainingPassThroughAmount();

            if ($remaining <= 0) {
                throw new \RuntimeException('Nilai pass through pada paket tidak valid.');
            }

            $invoice = Invoice::create([
                'user_id' => $ownerId,
                'created_by' => $createdBy,
                'customer_service_id' => $customerServiceId,
                'customer_service_name' => $customerServiceName,
                'client_name' => $clientName,
                'client_whatsapp' => $clientWhatsapp,
                'client_address' => $clientAddress ?: null,
                'number' => $this->generateInvoiceNumber(),
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'status' => 'belum bayar',
                'total' => $package->packagePrice,
                'type' => $package->customerType === PassThroughPackage::CUSTOMER_TYPE_NEW
                    ? Invoice::TYPE_PASS_THROUGH_NEW
                    : Invoice::TYPE_PASS_THROUGH_EXISTING,
                'reference_invoice_id' => null,
                'down_payment' => 0,
                'down_payment_due' => null,
                'payment_date' => null,
            ]);

            foreach ($this->makeInvoiceItems($package, $remaining) as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'category_id' => null,
                    'description' => $item['description'],
                    'quantity' => 1,
                    'price' => $item['amount'],
                ]);
            }

            $debtUserId = $attributes['debt_user_id']
                ?? $createdBy
                ?? $ownerId;

            Debt::create([
                'user_id' => $debtUserId,
                'invoice_id' => $invoice->id,
                'description' => $invoice->transactionDescription(),
                'related_party' => $clientName ?: $clientWhatsapp,
                'type' => Debt::TYPE_PASS_THROUGH,
                'amount' => $remaining,
                'due_date' => $dueDate,
                'status' => Debt::STATUS_BELUM_LUNAS,
                'daily_deduction' => $package->dailyDeduction,
            ]);

            return $invoice->load('items', 'customerService');
        });
    }

    protected function makeInvoiceItems(PassThroughPackage $package, float $remaining): array
    {
        $items = [];

        if ($package->customerType === PassThroughPackage::CUSTOMER_TYPE_NEW && $package->accountCreationFee > 0) {
            $items[] = [
                'description' => 'Biaya Pembuatan Akun Iklan',
                'amount' => $package->accountCreationFee,
            ];
        }

        if ($package->maintenanceFee > 0) {
            $items[] = [
                'description' => 'Jasa Maintenance',
                'amount' => $package->maintenanceFee,
            ];
        }

        if ($package->renewalFee > 0) {
            $items[] = [
                'description' => 'Biaya Perpanjangan',
                'amount' => $package->renewalFee,
            ];
        }

        $items[] = [
            'description' => 'Dana Pass Through',
            'amount' => $remaining,
        ];

        return $items;
    }

    protected function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $count = Invoice::whereDate('created_at', today())->count();
        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        return "{$date}-{$sequence}";
    }
}

