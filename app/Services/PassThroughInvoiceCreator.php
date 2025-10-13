<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Debt;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Support\PassThroughPackage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PassThroughInvoiceCreator
{
    /**
     * Membuat invoice pass through beserta catatan hutang terkait berdasarkan paket yang dipilih.
     */
    public function create(array $attributes): Invoice
    {
        return DB::transaction(function () use ($attributes) {
            $ownerId = $attributes['owner_id'] ?? null;
            $createdBy = $attributes['created_by'] ?? null;
            $customerServiceId = $attributes['customer_service_id'] ?? null;
            $customerServiceName = $attributes['customer_service_name'] ?? null;
            $clientName = $attributes['client_name'] ?? null;
            $clientWhatsapp = $attributes['client_whatsapp'] ?? null;
            $clientAddress = $attributes['client_address'] ?? null;
            $dueDate = $attributes['due_date'] ?? null;
            $issueDate = $attributes['issue_date'] ?? now();
            $issueDateCarbon = $issueDate instanceof Carbon ? $issueDate : Carbon::parse($issueDate);

            $customerType = $attributes['customer_type'] ?? PassThroughPackage::CUSTOMER_TYPE_NEW;
            $dailyBalance = (float) ($attributes['daily_balance'] ?? 0);
            $estimatedDuration = (int) ($attributes['estimated_duration'] ?? 0);
            $maintenanceFee = (float) ($attributes['maintenance_fee'] ?? 0);
            $accountCreationFee = (float) ($attributes['account_creation_fee'] ?? 0);

            if ($customerType !== PassThroughPackage::CUSTOMER_TYPE_NEW) {
                $accountCreationFee = 0;
            }

            $passThroughAmount = round($dailyBalance * $estimatedDuration, 2);

            if ($passThroughAmount <= 0) {
                throw new \RuntimeException('Nilai pass through tidak boleh 0.');
            }

            $total = round($passThroughAmount + $maintenanceFee + $accountCreationFee, 2);

            $invoice = Invoice::create([
                'user_id' => $ownerId,
                'created_by' => $createdBy,
                'customer_service_id' => $customerServiceId,
                'customer_service_name' => $customerServiceName,
                'client_name' => $clientName,
                'client_whatsapp' => $clientWhatsapp,
                'client_address' => $clientAddress ?: null,
                'number' => $this->generateInvoiceNumber(),
                'issue_date' => $issueDateCarbon,
                'due_date' => $dueDate,
                'status' => 'belum bayar',
                'total' => $total,
                'type' => $customerType === PassThroughPackage::CUSTOMER_TYPE_NEW
                    ? Invoice::TYPE_PASS_THROUGH_NEW
                    : Invoice::TYPE_PASS_THROUGH_EXISTING,
                'reference_invoice_id' => null,
                'down_payment' => 0,
                'down_payment_due' => null,
                'payment_date' => null,
            ]);

            foreach ($this->makeInvoiceItems($passThroughAmount, $maintenanceFee, $accountCreationFee, $customerType, $dailyBalance, $estimatedDuration) as $item) {
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
                'amount' => $passThroughAmount,
                'due_date' => $dueDate,
                'status' => Debt::STATUS_BELUM_LUNAS,
                'daily_deduction' => $dailyBalance,
            ]);

            $this->recordTransactions($maintenanceFee, $accountCreationFee, $invoice, $ownerId ?? $createdBy, $issueDateCarbon);

            return $invoice->load('items', 'customerService');
        });
    }

    protected function makeInvoiceItems(
        float $passThroughAmount,
        float $maintenanceFee,
        float $accountCreationFee,
        string $customerType,
        float $dailyBalance,
        int $estimatedDuration
    ): array
    {
        $items = [];

        if ($customerType === PassThroughPackage::CUSTOMER_TYPE_NEW && $accountCreationFee > 0) {
            $items[] = [
                'description' => 'Biaya Pembuatan Akun Iklan',
                'amount' => $accountCreationFee,
            ];
        }

        if ($maintenanceFee > 0) {
            $items[] = [
                'description' => 'Jasa Maintenance',
                'amount' => $maintenanceFee,
            ];
        }

        $items[] = [
            'description' => 'Dana Pass Through (' . $this->formatCurrency($dailyBalance) . ' x ' . max($estimatedDuration, 0) . ' hari)',
            'amount' => $passThroughAmount,
        ];

        return $items;
    }

    protected function recordTransactions(float $maintenanceFee, float $accountCreationFee, Invoice $invoice, ?int $userId, Carbon $issueDate): void
    {
        $category = Category::query()
            ->where('name', 'Penjualan Iklan')
            ->where('type', 'pemasukan')
            ->first();

        if (! $category) {
            return;
        }

        $transactions = [];

        if ($maintenanceFee > 0) {
            $transactions[] = [
                'amount' => $maintenanceFee,
                'description' => 'Jasa Maintenance - ' . ($invoice->client_name ?: $invoice->client_whatsapp ?: $invoice->number),
            ];
        }

        if ($accountCreationFee > 0 && $invoice->type === Invoice::TYPE_PASS_THROUGH_NEW) {
            $transactions[] = [
                'amount' => $accountCreationFee,
                'description' => 'Biaya Pembuatan Akun - ' . ($invoice->client_name ?: $invoice->client_whatsapp ?: $invoice->number),
            ];
        }

        foreach ($transactions as $transaction) {
            Transaction::create([
                'category_id' => $category->id,
                'user_id' => $userId,
                'amount' => $transaction['amount'],
                'description' => $transaction['description'],
                'date' => $issueDate->toDateString(),
            ]);
        }
    }

    protected function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $count = Invoice::whereDate('created_at', today())->count();
        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        return "{$date}-{$sequence}";
    }

    protected function formatCurrency(float $value): string
    {
        $rounded = (int) round($value);

        return number_format($rounded, 0, ',', '.');
    }
}

