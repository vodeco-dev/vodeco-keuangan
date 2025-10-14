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
     * Membuat Invoices Iklan beserta catatan hutang terkait berdasarkan paket yang dipilih.
     */
    public function create(PassThroughPackage $package, int $quantity, array $attributes): Invoice
    {
        return DB::transaction(function () use ($package, $quantity, $attributes) {
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

            $normalizedQuantity = max($quantity, 1);
            $customerType = $package->customerType;
            $description = trim((string) ($attributes['description'] ?? ''));

            $durationDays = (int) ($attributes['duration_days'] ?? $package->durationDays ?? 0);
            if ($durationDays <= 0 && $package->durationDays > 0) {
                $durationDays = $package->durationDays;
            }

            $dailyBalanceUnit = (float) ($attributes['daily_balance_unit'] ?? $package->dailyBalance ?? 0);
            if ($dailyBalanceUnit <= 0 && $package->dailyBalance > 0) {
                $dailyBalanceUnit = $package->dailyBalance;
            }

            $maintenanceUnit = (float) ($attributes['maintenance_unit'] ?? $package->maintenanceFee ?? 0);
            if ($maintenanceUnit < 0) {
                $maintenanceUnit = 0;
            }

            $accountCreationUnit = (float) ($attributes['account_creation_unit'] ?? $package->accountCreationFee ?? 0);
            if ($customerType !== PassThroughPackage::CUSTOMER_TYPE_NEW) {
                $accountCreationUnit = 0;
            }

            $adBudgetUnit = (float) ($attributes['ad_budget_unit'] ?? round($dailyBalanceUnit * $durationDays, 2));
            if ($adBudgetUnit <= 0) {
                $adBudgetUnit = round($dailyBalanceUnit * $durationDays, 2);
            }

            $adBudgetTotal = round($adBudgetUnit * $normalizedQuantity, 2);
            $maintenanceTotal = round($maintenanceUnit * $normalizedQuantity, 2);
            $accountCreationTotal = round($accountCreationUnit * $normalizedQuantity, 2);

            if ($adBudgetTotal <= 0) {
                throw new \RuntimeException('Nilai Invoices Iklan tidak boleh 0.');
            }

            $total = round($adBudgetTotal + $maintenanceTotal + $accountCreationTotal, 2);

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

            foreach ($this->makeInvoiceItems($package, $normalizedQuantity, $description, $dailyBalanceUnit, $durationDays, $adBudgetUnit, $maintenanceUnit, $accountCreationUnit) as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'category_id' => null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['unit_amount'],
                ]);
            }

            $debtUserId = $attributes['debt_user_id']
                ?? $createdBy
                ?? $ownerId;

            $dailyBalanceTotal = round($dailyBalanceUnit * $normalizedQuantity, 2);
            $debtDescription = $this->makeDebtDescription($package, $description, $normalizedQuantity);

            Debt::create([
                'user_id' => $debtUserId,
                'invoice_id' => $invoice->id,
                'description' => $debtDescription,
                'related_party' => $clientName ?: $clientWhatsapp,
                'type' => Debt::TYPE_PASS_THROUGH,
                'amount' => $adBudgetTotal,
                'due_date' => $dueDate,
                'status' => Debt::STATUS_BELUM_LUNAS,
                'daily_deduction' => $dailyBalanceTotal,
            ]);

            $this->recordTransactions($maintenanceTotal, $accountCreationTotal, $normalizedQuantity, $invoice, $ownerId ?? $createdBy, $issueDateCarbon);

            return $invoice->load('items', 'customerService');
        });
    }

    protected function makeInvoiceItems(
        PassThroughPackage $package,
        int $quantity,
        string $customDescription,
        float $dailyBalanceUnit,
        int $durationDays,
        float $adBudgetUnit,
        float $maintenanceUnit,
        float $accountCreationUnit
    ): array
    {
        $items = [];

        if ($package->customerType === PassThroughPackage::CUSTOMER_TYPE_NEW && $accountCreationUnit > 0) {
            $items[] = [
                'description' => 'Biaya Pembuatan Akun Iklan',
                'quantity' => $quantity,
                'unit_amount' => $accountCreationUnit,
            ];
        }

        if ($maintenanceUnit > 0) {
            $items[] = [
                'description' => 'Jasa Maintenance',
                'quantity' => $quantity,
                'unit_amount' => $maintenanceUnit,
            ];
        }

        $items[] = [
            'description' => $this->makeAdBudgetDescription($package, $customDescription, $dailyBalanceUnit, $durationDays),
            'quantity' => $quantity,
            'unit_amount' => $adBudgetUnit,
        ];

        return $items;
    }

    protected function recordTransactions(float $maintenanceTotal, float $accountCreationTotal, int $quantity, Invoice $invoice, ?int $userId, Carbon $issueDate): void
    {
        $category = Category::query()
            ->where('name', 'Penjualan Iklan')
            ->where('type', 'pemasukan')
            ->first();

        if (! $category) {
            return;
        }

        $transactions = [];

        if ($maintenanceTotal > 0) {
            $transactions[] = [
                'amount' => $maintenanceTotal,
                'description' => 'Jasa Maintenance' . ($quantity > 1 ? ' (x' . $quantity . ')' : '') . ' - ' . ($invoice->client_name ?: $invoice->client_whatsapp ?: $invoice->number),
            ];
        }

        if ($accountCreationTotal > 0 && $invoice->type === Invoice::TYPE_PASS_THROUGH_NEW) {
            $transactions[] = [
                'amount' => $accountCreationTotal,
                'description' => 'Biaya Pembuatan Akun' . ($quantity > 1 ? ' (x' . $quantity . ')' : '') . ' - ' . ($invoice->client_name ?: $invoice->client_whatsapp ?: $invoice->number),
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

    protected function makeAdBudgetDescription(PassThroughPackage $package, string $customDescription, float $dailyBalance, int $durationDays): string
    {
        $parts = [];

        $customDescription = trim($customDescription);

        if ($customDescription !== '') {
            $parts[] = $customDescription;
        } elseif ($package->name !== '') {
            $parts[] = $package->name;
        }

        $dailyLabel = $this->formatCurrency($dailyBalance);
        $durationLabel = max($durationDays, 0);

        $parts[] = 'Dana Invoices Iklan (' . $dailyLabel . ' x ' . $durationLabel . ' hari)';

        return implode(' – ', $parts);
    }

    protected function makeDebtDescription(PassThroughPackage $package, string $customDescription, int $quantity): string
    {
        $label = trim($customDescription);

        if ($label === '') {
            $label = $package->name;
        }

        $quantityLabel = $quantity > 1 ? ' (x' . $quantity . ')' : '';

        return trim('Invoices Iklan ' . ($label !== '' ? $label : '')) . $quantityLabel;
    }
}

