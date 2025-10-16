<?php
use Illuminate\Support\Str;

$tabLabels = [
    'down_payment' => 'Down Payment',
    'full_payment' => 'Bayar Lunas',
    'pass_through' => 'Invoices Iklan',
    'settlement' => 'Pelunasan',
];

$allowedTransactions = $allowedTransactions ?? array_keys($tabLabels);
$allowedTransactions = array_values(array_intersect(array_keys($tabLabels), $allowedTransactions));
if (empty($allowedTransactions)) {
    $allowedTransactions = array_keys($tabLabels);
}

$defaultTransaction = $defaultTransaction ?? 'down_payment';
if (! in_array($defaultTransaction, $allowedTransactions, true)) {
    $defaultTransaction = $allowedTransactions[0];
}

$componentId = $attributes->get('id') ?? 'invoice-tabs-' . Str::random(8);
$formId = $formId ?? 'invoice-form';
$variant = $variant ?? 'internal';
$categoryOptions = $categoryOptions ?? [];
$items = $items ?? [];
$downPaymentFieldLabel = $downPaymentFieldLabel ?? 'Nominal Down Payment';
$downPaymentPlaceholder = $downPaymentPlaceholder ?? 'Contoh: 5.000.000';
$downPaymentHelp = $downPaymentHelp ?? null;
$downPaymentValue = $downPaymentValue ?? '';
$downPaymentRequired = $downPaymentRequired ?? false;
$addItemButtonLabel = $addItemButtonLabel ?? 'Tambah Item';
$totalLabel = $totalLabel ?? 'Total Invoice';
$showTotal = $showTotal ?? true;
$settlementInvoiceNumber = $settlementInvoiceNumber ?? '';
$settlementRemainingBalance = $settlementRemainingBalance ?? '';
$settlementPaidAmount = $settlementPaidAmount ?? '';
$settlementPaymentStatus = $settlementPaymentStatus ?? null;
$currencyPlaceholder = $currencyPlaceholder ?? 'Contoh: 1.500.000';

// New props for pass-through content
$passThroughPackages = $passThroughPackages ?? collect();
$passThroughConfig = $passThroughConfig ?? [];

$forwardedAttributes = $attributes->except([
    'id',
    'form-id',
    'items',
    'category-options',
    'allowed-transactions',
    'default-transaction',
    'variant',
    'down-payment-field-label',
    'down-payment-placeholder',
    'down-payment-help',
    'down-payment-required',
    'add-item-button-label',
    'total-label',
    'down-payment-value',
    'settlement-invoice-number',
    'settlement-remaining-balance',
    'settlement-paid-amount',
    'settlement-payment-status',
    'currency-placeholder',
    'pass-through-packages',
    'pass-through-config',
]);
?>

<div
    {{ $forwardedAttributes->merge(['class' => 'space-y-6']) }}
    id="{{ $componentId }}"
    x-data="invoiceTabsComponent({
        id: '{{ $componentId }}',
        defaultTab: '{{ $defaultTransaction }}',
        variant: '{{ $variant }}',
    })"
    x-init="init()"
    data-form-id="{{ $formId }}"
    data-category-options='@json($categoryOptions)'
>
    <input type="hidden" name="transaction_type" value="{{ $defaultTransaction }}" x-model="activeTab">

    <div class="space-y-3">
        <h3 class="text-lg font-medium text-gray-900">Jenis Transaksi</h3>
        <div class="flex flex-wrap gap-2">
            @foreach ($allowedTransactions as $transaction)
                <button
                    type="button"
                    @click="setTab('{{ $transaction }}')"
                    :class="tabClass('{{ $transaction }}')"
                >
                    {{ $tabLabels[$transaction] }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="space-y-6" x-cloak>
        @if ($downPaymentFieldLabel)
            <div x-show="activeTab === 'down_payment'" data-down-payment-visible>
                <label for="down_payment_due" class="block text-sm font-medium text-gray-700">{{ $downPaymentFieldLabel }}</label>
                <input
                    type="text"
                    name="down_payment_due"
                    id="down_payment_due"
                    value="{{ old('down_payment_due', $downPaymentValue) }}"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 price-input"
                    placeholder="{{ $downPaymentPlaceholder }}"
                    data-transaction-scope="down-payment"
                    data-down-payment-required="{{ $downPaymentRequired ? 'true' : 'false' }}"
                >
                @if ($downPaymentHelp)
                    <p class="mt-1 text-xs text-gray-500">{{ $downPaymentHelp }}</p>
                @endif
                @error('down_payment_due')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <div x-show="! ['settlement', 'pass_through'].includes(activeTab)" data-items-wrapper x-cloak>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Daftar Item</h3>
                <button type="button" data-add-item class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    {{ $addItemButtonLabel }}
                </button>
            </div>

            <div data-items-container class="space-y-4">
                @foreach ($items as $index => $item)
                    <div class="grid grid-cols-12 gap-4 invoice-item bg-gray-50 p-4 rounded-xl" data-invoice-item>
                        <div class="col-span-12">
                            <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                            <textarea name="items[{{ $index }}][description]" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 description" data-transaction-scope="line-item" :required="!['pass_through', 'settlement'].includes(activeTab)">{{ $item['description'] ?? '' }}</textarea>
                        </div>
                        <div class="col-span-12 md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Kategori</label>
                            <select name="items[{{ $index }}][category_id]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 category-select" data-transaction-scope="line-item" :required="!['pass_through', 'settlement'].includes(activeTab)">
                                <option value="">Pilih kategori pemasukan</option>
                                @foreach ($categoryOptions as $option)
                                    <option value="{{ $option['id'] }}" @selected(($item['category_id'] ?? '') == $option['id'])>{{ $option['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Kuantitas</label>
                            <input type="number" name="items[{{ $index }}][quantity]" class="quantity mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ $item['quantity'] ?? 1 }}" min="1" data-role="quantity-input" data-transaction-scope="line-item" :required="!['pass_through', 'settlement'].includes(activeTab)">
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Harga Satuan</label>
                            <input type="text" name="items[{{ $index }}][price]" class="price-input mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ $item['price'] ?? '' }}" data-role="price-input" data-transaction-scope="line-item" :required="!['pass_through', 'settlement'].includes(activeTab)">
                        </div>
                        <div class="col-span-12 md:col-span-1 flex items-end justify-end">
                            <button type="button" class="text-sm font-semibold text-red-600 hover:text-red-700" data-remove-item>Hapus</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div x-show="activeTab === 'settlement'" data-tab-visible="settlement" x-cloak>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Detail Pelunasan</h3>
            <div class="space-y-6">
                </div>
        </div>

        <div x-show="activeTab === 'pass_through'" data-tab-visible="pass_through" x-cloak>
            <div x-data='passThroughForm(@json($passThroughConfig))'>
                <div class="space-y-6 rounded-2xl border border-gray-200 bg-gray-50 p-6">
                    <div class="space-y-2">
                        <h3 class="text-lg font-semibold text-gray-900">Invoices Iklan</h3>
                        <p class="text-sm text-gray-600">Gunakan paket yang tersedia untuk menghitung dana iklan, biaya maintenance, serta biaya pembuatan akun secara otomatis.</p>
                    </div>

                    @if ($passThroughPackages->isEmpty())
                        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                            Belum ada paket Invoices Iklan yang dapat dipilih. Hubungi tim internal untuk menambahkan paket terlebih dahulu.
                        </div>
                    @else
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="pass_through_package_id_public">Pilihan Paket</label>
                            <select
                                name="pass_through_package_id"
                                id="pass_through_package_id_public"
                                x-model="passThroughPackageId"
                                class="mt-1 block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                                @if ($passThroughPackages->isEmpty()) disabled @endif
                            >
                                <option value="" disabled>Pilih paket</option>
                                <template x-for="pkg in passThroughPackages" :key="pkg.id">
                                    <option :value="pkg.id" x-text="formatPackageOption(pkg)"></option>
                                </template>
                            </select>
                            @error('pass_through_package_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="pass_through_quantity_public">Kuantitas</label>
                            <input
                                type="number"
                                min="1"
                                id="pass_through_quantity_public"
                                x-model="passThroughQuantityInput"
                                @input="updatePassThroughQuantity($event.target.value)"
                                @blur="normalizePassThroughQuantity()"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                                placeholder="1"
                                @if ($passThroughPackages->isEmpty()) disabled @endif
                            >
                            @error('pass_through_quantity')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700" for="pass_through_total_display_public">Harga Total</label>
                            <input
                                type="text"
                                id="pass_through_total_display_public"
                                :value="formatCurrency(passThroughTotalPrice())"
                                class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-100 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                readonly
                            >
                            @error('pass_through_total_price')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    @endif

                    <input type="hidden" name="pass_through_quantity" :value="passThroughQuantity()">
                    <input type="hidden" name="pass_through_ad_budget_total" :value="passThroughAdBudgetTotal()">
                    <input type="hidden" name="pass_through_maintenance_total" :value="passThroughMaintenanceTotal()">
                    <input type="hidden" name="pass_through_account_creation_total" :value="passThroughAccountCreationTotal()">
                    <input type="hidden" name="pass_through_total_price" :value="passThroughTotalPrice()">
                    <input type="hidden" name="pass_through_daily_balance_total" :value="passThroughDailyBalanceTotal()">
                    <input type="hidden" name="pass_through_duration_days" :value="passThroughDurationDays()">
                </div>

                <div class="rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm" x-show="hasPassThroughPackageSelected()" x-cloak>
                    <h4 class="text-base font-semibold text-gray-900">Ringkasan Paket</h4>
                    <dl class="mt-4 grid grid-cols-1 gap-4 text-sm text-gray-700 md:grid-cols-2">
                        </dl>
                </div>
            </div>
        </div>
    </div>

    @if ($showTotal)
        <div x-show="!['pass_through', 'settlement'].includes(activeTab)" class="flex flex-col items-end gap-2 border-t border-gray-200 pt-6" data-total-wrapper>
            <p class="text-lg font-semibold text-gray-900">{{ $totalLabel }}</p>
            <p data-total-amount class="text-2xl font-bold text-indigo-600">Rp 0</p>
        </div>
    @endif

    <template data-item-template>
        <div class="grid grid-cols-12 gap-4 invoice-item bg-gray-50 p-4 rounded-xl" data-invoice-item>
            <div class="col-span-12">
                <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                <textarea name="items[__INDEX__][description]" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 description" data-transaction-scope="line-item" :required="!['pass_through', 'settlement'].includes(activeTab)"></textarea>
            </div>
            <div class="col-span-12 md:col-span-3">
                <label class="block text-sm font-medium text-gray-700">Kategori</label>
                <select name="items[__INDEX__][category_id]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 category-select" data-transaction-scope="line-item" :required="!['pass_through', 'settlement'].includes(activeTab)"></select>
            </div>
            <div class="col-span-6 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Kuantitas</label>
                <input type="number" name="items[__INDEX__][quantity]" class="quantity mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="1" min="1" data-role="quantity-input" data-transaction-scope="line-item" :required="!['pass_through', 'settlement'].includes(activeTab)">
            </div>
            <div class="col-span-6 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Harga Satuan</label>
                <input type="text" name="items[__INDEX__][price]" class="price-input mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" data-role="price-input" data-transaction-scope="line-item" :required="!['pass_through', 'settlement'].includes(activeTab)">
            </div>
            <div class="col-span-12 md:col-span-1 flex items-end justify-end">
                <button type="button" class="text-sm font-semibold text-red-600 hover:text-red-700" data-remove-item>Hapus</button>
            </div>
        </div>
    </template>
</div>
