<?php
use Illuminate\Support\Str;

$tabLabels = [
    'down_payment' => 'Down Payment',
    'full_payment' => 'Bayar Lunas',
    'pass_through' => 'Pass Through',
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

        <div x-show="activeTab !== 'settlement'" data-items-wrapper x-cloak>
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
                            <textarea name="items[{{ $index }}][description]" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 description" data-transaction-scope="line-item" required>{{ $item['description'] ?? '' }}</textarea>
                            @error('items.' . $index . '.description')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Kategori</label>
                            <select name="items[{{ $index }}][category_id]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 category-select" data-transaction-scope="line-item" required>
                                <option value="">Pilih kategori pemasukan</option>
                                @foreach ($categoryOptions as $option)
                                    <option value="{{ $option['id'] }}" @selected(($item['category_id'] ?? '') == $option['id'])>{{ $option['name'] }}</option>
                                @endforeach
                            </select>
                            @error('items.' . $index . '.category_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Kuantitas</label>
                            <input type="number" name="items[{{ $index }}][quantity]" class="quantity mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ $item['quantity'] ?? 1 }}" min="1" data-role="quantity-input" data-transaction-scope="line-item" required>
                            @error('items.' . $index . '.quantity')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Harga Satuan</label>
                            <input type="text" name="items[{{ $index }}][price]" class="price-input mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ $item['price'] ?? '' }}" data-role="price-input" data-transaction-scope="line-item" required>
                            @error('items.' . $index . '.price')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
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
            <div data-settlement-feedback class="space-y-3 mb-6">
                <div data-settlement-error class="hidden rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"></div>
                <div data-settlement-summary class="hidden rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">
                    <h4 class="text-sm font-semibold text-indigo-800">Ringkasan Invoice Referensi</h4>
                    <dl class="mt-3 grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-indigo-600">Nama Klien</dt>
                            <dd class="font-semibold" data-settlement-client>-</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-indigo-600">Status</dt>
                            <dd data-settlement-status>-</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-indigo-600">Nomor WhatsApp</dt>
                            <dd data-settlement-whatsapp>-</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-indigo-600">Total Invoice</dt>
                            <dd data-settlement-total>-</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-indigo-600">Down Payment Tercatat</dt>
                            <dd data-settlement-down-payment>-</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-indigo-600">Sisa Tagihan</dt>
                            <dd data-settlement-remaining>-</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-indigo-600">Jatuh Tempo</dt>
                            <dd data-settlement-due-date>-</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium uppercase tracking-wide text-indigo-600">Alamat Klien</dt>
                            <dd data-settlement-address class="whitespace-pre-line">-</dd>
                        </div>
                    </dl>
                </div>
            </div>
            <div class="space-y-6">
                <div>
                    <label for="settlement_invoice_number" class="block text-sm font-medium text-gray-700">Nomor Invoice Acuan</label>
                    <input type="text" name="settlement_invoice_number" id="settlement_invoice_number" value="{{ old('settlement_invoice_number', $settlementInvoiceNumber) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" data-transaction-scope="settlement" data-settlement-required="true">
                    @error('settlement_invoice_number')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="settlement_remaining_balance" class="block text-sm font-medium text-gray-700">Sisa Tagihan</label>
                    <input type="text" name="settlement_remaining_balance" id="settlement_remaining_balance" value="{{ old('settlement_remaining_balance', $settlementRemainingBalance) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 price-input" placeholder="{{ $currencyPlaceholder }}" data-transaction-scope="settlement" data-settlement-required="true" data-role="price-input">
                    @error('settlement_remaining_balance')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="settlement_paid_amount" class="block text-sm font-medium text-gray-700">Nominal Dibayarkan</label>
                    <input type="text" name="settlement_paid_amount" id="settlement_paid_amount" value="{{ old('settlement_paid_amount', $settlementPaidAmount) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 price-input" placeholder="{{ $currencyPlaceholder }}" data-transaction-scope="settlement" data-settlement-required="true" data-role="price-input">
                    @error('settlement_paid_amount')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-700 mb-2">Status Pelunasan</span>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-6">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="settlement_payment_status" value="paid_full" @checked(old('settlement_payment_status', $settlementPaymentStatus) === 'paid_full') data-transaction-scope="settlement" data-settlement-required="true">
                            <span>Bayar Lunas</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="settlement_payment_status" value="paid_partial" @checked(old('settlement_payment_status', $settlementPaymentStatus) === 'paid_partial') data-transaction-scope="settlement" data-settlement-required="true">
                            <span>Bayar Sebagian</span>
                        </label>
                    </div>
                    @error('settlement_payment_status')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    @if ($showTotal)
        <div class="flex flex-col items-end gap-2 border-t border-gray-200 pt-6" data-total-wrapper>
            <p class="text-lg font-semibold text-gray-900">{{ $totalLabel }}</p>
            <p data-total-amount class="text-2xl font-bold text-indigo-600">Rp 0</p>
        </div>
    @endif

    <template data-item-template>
        <div class="grid grid-cols-12 gap-4 invoice-item bg-gray-50 p-4 rounded-xl" data-invoice-item>
            <div class="col-span-12">
                <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                <textarea name="items[__INDEX__][description]" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 description" data-transaction-scope="line-item" required></textarea>
            </div>
            <div class="col-span-12 md:col-span-3">
                <label class="block text-sm font-medium text-gray-700">Kategori</label>
                <select name="items[__INDEX__][category_id]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 category-select" data-transaction-scope="line-item" required></select>
            </div>
            <div class="col-span-6 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Kuantitas</label>
                <input type="number" name="items[__INDEX__][quantity]" class="quantity mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="1" min="1" data-role="quantity-input" data-transaction-scope="line-item" required>
            </div>
            <div class="col-span-6 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Harga Satuan</label>
                <input type="text" name="items[__INDEX__][price]" class="price-input mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" data-role="price-input" data-transaction-scope="line-item" required>
            </div>
            <div class="col-span-12 md:col-span-1 flex items-end justify-end">
                <button type="button" class="text-sm font-semibold text-red-600 hover:text-red-700" data-remove-item>Hapus</button>
            </div>
        </div>
    </template>
</div>

@once
    <script>
        window.invoiceTabsComponent = function (config) {
            return {
                id: config.id,
                variant: config.variant || 'internal',
                activeTab: config.defaultTab || 'down_payment',
                form: null,
                itemsContainer: null,
                addItemButton: null,
                totalElement: null,
                categoryOptions: [],
                itemIndex: 0,
                referenceUrlTemplate: '',
                settlementInvoiceInput: null,
                settlementRemainingInput: null,
                settlementPaidInput: null,
                settlementSummary: null,
                settlementSummaryFields: {},
                settlementError: null,
                settlementFetchTimeout: null,
                settlementAbortController: null,
                init() {
                    this.form = document.getElementById(this.$el.dataset.formId) || this.$el.closest('form');
                    this.itemsContainer = this.$el.querySelector('[data-items-container]');
                    this.addItemButton = this.$el.querySelector('[data-add-item]');
                    this.totalElement = this.$el.querySelector('[data-total-amount]');
                    this.categoryOptions = JSON.parse(this.$el.dataset.categoryOptions || '[]');
                    this.itemIndex = this.itemsContainer ? this.itemsContainer.querySelectorAll('[data-invoice-item]').length : 0;
                    this.referenceUrlTemplate = this.$el.dataset.referenceUrlTemplate || '';
                    this.settlementInvoiceInput = this.$el.querySelector('[name="settlement_invoice_number"]');
                    this.settlementRemainingInput = this.$el.querySelector('[name="settlement_remaining_balance"]');
                    this.settlementPaidInput = this.$el.querySelector('[name="settlement_paid_amount"]');
                    this.settlementSummary = this.$el.querySelector('[data-settlement-summary]');
                    this.settlementError = this.$el.querySelector('[data-settlement-error]');
                    this.settlementSummaryFields = {
                        client: this.$el.querySelector('[data-settlement-client]'),
                        status: this.$el.querySelector('[data-settlement-status]'),
                        whatsapp: this.$el.querySelector('[data-settlement-whatsapp]'),
                        total: this.$el.querySelector('[data-settlement-total]'),
                        downPayment: this.$el.querySelector('[data-settlement-down-payment]'),
                        remaining: this.$el.querySelector('[data-settlement-remaining]'),
                        dueDate: this.$el.querySelector('[data-settlement-due-date]'),
                        address: this.$el.querySelector('[data-settlement-address]'),
                    };

                    this.initPriceInputs();
                    this.initQuantityInputs();
                    this.setupAddItem();
                    this.setupRemoveItem();
                    this.setupFormSubmit();
                    this.setupSettlementReferenceLookup();
                    this.applyTabScope();
                    this.updateTotal();

                    window.dispatchEvent(new CustomEvent('invoice-transaction-tab-changed', {
                        detail: { tab: this.activeTab, id: this.id },
                    }));
                },
                setTab(tab) {
                    this.activeTab = tab;
                    this.applyTabScope();
                    this.updateTotal();
                    window.dispatchEvent(new CustomEvent('invoice-transaction-tab-changed', {
                        detail: { tab: this.activeTab, id: this.id },
                    }));
                },
                tabClass(tab) {
                    const activeClasses = this.variant === 'public'
                        ? 'px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white shadow'
                        : 'px-4 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white shadow';
                    const inactiveClasses = 'px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200';

                    return this.activeTab === tab ? activeClasses : inactiveClasses;
                },
                applyTabScope() {
                    const scope = this.activeTab;
                    const isSettlement = scope === 'settlement';
                    const isPassThrough = scope === 'pass_through';
                    this.$el.querySelectorAll('[data-transaction-scope]').forEach((element) => {
                        const targetScope = element.dataset.transactionScope;
                        if (targetScope === 'settlement') {
                            const enable = isSettlement;
                            element.disabled = !enable;
                            if (element.dataset.settlementRequired === 'true') {
                                element.required = enable;
                            }
                            if (!enable && element.type === 'radio') {
                                element.checked = false;
                            }
                        } else if (targetScope === 'down-payment') {
                            const enable = scope === 'down_payment';
                            element.disabled = !enable;
                            if (element.dataset.downPaymentRequired === 'true') {
                                element.required = enable;
                            }
                        } else if (targetScope === 'line-item') {
                            const disable = isSettlement || isPassThrough;
                            element.disabled = disable;
                            if (disable) {
                                if (element.required && ! element.dataset.originalRequired) {
                                    element.dataset.originalRequired = '1';
                                }
                                element.required = false;
                            } else if (element.dataset.originalRequired === '1') {
                                element.required = true;
                                delete element.dataset.originalRequired;
                            }
                            if (! disable && element.dataset.originalRequired) {
                                delete element.dataset.originalRequired;
                            }
                        }
                    });

                    const itemsWrapper = this.$el.querySelector('[data-items-wrapper]');
                    if (itemsWrapper) {
                        itemsWrapper.style.display = isSettlement || isPassThrough ? 'none' : '';
                    }

                    const settlementSection = this.$el.querySelector('[data-tab-visible="settlement"]');
                    if (settlementSection) {
                        settlementSection.style.display = isSettlement ? '' : 'none';
                    }

                    if (this.addItemButton) {
                        const disabled = isSettlement || isPassThrough;
                        this.addItemButton.disabled = disabled;
                        this.addItemButton.classList.toggle('opacity-50', disabled);
                        this.addItemButton.classList.toggle('cursor-not-allowed', disabled);
                    }

                    const totalWrapper = this.$el.querySelector('[data-total-wrapper]');
                    if (totalWrapper) {
                        totalWrapper.style.display = isSettlement || isPassThrough ? 'none' : '';
                    }

                    this.handleSettlementScopeChange();
                },
                initPriceInputs() {
                    this.$el.querySelectorAll('[data-role="price-input"]').forEach((input) => {
                        this.formatPrice(input);
                        input.addEventListener('input', () => {
                            this.formatPrice(input);
                            this.updateTotal();
                        });
                        input.addEventListener('blur', () => this.formatPrice(input));
                    });
                },
                initQuantityInputs() {
                    this.$el.querySelectorAll('[data-role="quantity-input"]').forEach((input) => {
                        input.addEventListener('input', () => this.updateTotal());
                    });
                },
                setupAddItem() {
                    if (!this.addItemButton || !this.itemsContainer) {
                        return;
                    }

                    const template = this.$el.querySelector('template[data-item-template]');
                    this.addItemButton.addEventListener('click', () => {
                        if (!template) {
                            return;
                        }

                        const html = template.innerHTML.replace(/__INDEX__/g, String(this.itemIndex));
                        const fragment = document.createElement('template');
                        fragment.innerHTML = html.trim();
                        const element = fragment.content.firstElementChild;

                        if (!element) {
                            return;
                        }

                        const categorySelect = element.querySelector('select[name^="items"][name$="[category_id]"]');
                        if (categorySelect) {
                            categorySelect.innerHTML = ['<option value="">Pilih kategori pemasukan</option>']
                                .concat(this.categoryOptions.map((option) => `<option value="${option.id}">${option.name}</option>`))
                                .join('');
                        }

                        this.itemsContainer.appendChild(element);

                        element.querySelectorAll('[data-role="price-input"]').forEach((input) => {
                            this.formatPrice(input);
                            input.addEventListener('input', () => {
                                this.formatPrice(input);
                                this.updateTotal();
                            });
                            input.addEventListener('blur', () => this.formatPrice(input));
                        });

                        element.querySelectorAll('[data-role="quantity-input"]').forEach((input) => {
                            input.addEventListener('input', () => this.updateTotal());
                        });

                        this.itemIndex += 1;
                        this.updateTotal();
                    });
                },
                setupRemoveItem() {
                    if (!this.itemsContainer) {
                        return;
                    }

                    this.itemsContainer.addEventListener('click', (event) => {
                        const button = event.target.closest('[data-remove-item]');
                        if (!button) {
                            return;
                        }

                        const item = button.closest('[data-invoice-item]');
                        if (item) {
                            item.remove();
                            this.updateTotal();
                        }
                    });
                },
                setupFormSubmit() {
                    if (!this.form) {
                        return;
                    }

                    this.form.addEventListener('submit', () => {
                        this.$el.querySelectorAll('[data-role="price-input"]').forEach((input) => {
                            if (input.disabled) {
                                return;
                            }

                            input.value = input.dataset.rawValue || '';
                        });
                    });
                },
                setupSettlementReferenceLookup() {
                    if (!this.settlementInvoiceInput || !this.referenceUrlTemplate) {
                        return;
                    }

                    this.settlementInvoiceInput.addEventListener('input', () => {
                        this.scheduleSettlementReferenceFetch();
                    });

                    this.settlementInvoiceInput.addEventListener('blur', () => {
                        this.scheduleSettlementReferenceFetch(true);
                    });
                },
                handleSettlementScopeChange() {
                    if (!this.settlementInvoiceInput) {
                        return;
                    }

                    if (this.activeTab === 'settlement') {
                        this.scheduleSettlementReferenceFetch(true);
                    } else {
                        this.clearSettlementFeedback();
                    }
                },
                scheduleSettlementReferenceFetch(immediate = false) {
                    if (!this.settlementInvoiceInput || !this.referenceUrlTemplate) {
                        return;
                    }

                    if (this.activeTab !== 'settlement') {
                        this.clearSettlementFeedback();
                        return;
                    }

                    const number = (this.settlementInvoiceInput.value || '').trim();

                    if (this.settlementFetchTimeout) {
                        clearTimeout(this.settlementFetchTimeout);
                    }

                    if (!number) {
                        this.clearSettlementFeedback();
                        return;
                    }

                    const fetchAction = () => {
                        this.settlementFetchTimeout = null;
                        this.fetchSettlementReference(number);
                    };

                    if (immediate) {
                        fetchAction();
                    } else {
                        this.settlementFetchTimeout = setTimeout(fetchAction, 400);
                    }
                },
                fetchSettlementReference(number) {
                    if (!this.referenceUrlTemplate) {
                        return;
                    }

                    const url = this.referenceUrlTemplate.replace('__NUMBER__', encodeURIComponent(number));

                    if (this.settlementAbortController) {
                        this.settlementAbortController.abort();
                    }

                    this.settlementAbortController = new AbortController();

                    fetch(url, {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                        },
                        signal: this.settlementAbortController.signal,
                    })
                        .then(async (response) => {
                            if (!response.ok) {
                                let message = 'Gagal mengambil data invoice referensi.';

                                try {
                                    const payload = await response.json();
                                    if (payload && typeof payload.message === 'string') {
                                        message = payload.message;
                                    }
                                } catch (error) {
                                    // ignore parsing error
                                }

                                this.showSettlementError(message);
                                return;
                            }

                            const data = await response.json();
                            this.showSettlementSummary(data);
                        })
                        .catch((error) => {
                            if (error.name === 'AbortError') {
                                return;
                            }

                            this.showSettlementError('Tidak dapat memuat data invoice referensi.');
                        })
                        .finally(() => {
                            this.settlementAbortController = null;
                        });
                },
                showSettlementSummary(data) {
                    this.clearSettlementFeedback();

                    if (!this.settlementSummary) {
                        return;
                    }

                    const currencyFormatter = (value) => this.formatCurrency(value ?? 0);

                    if (this.settlementSummaryFields.client) {
                        this.settlementSummaryFields.client.textContent = data?.client_name || '-';
                    }

                    if (this.settlementSummaryFields.status) {
                        const status = (data?.status || '-').toString().replace(/_/g, ' ');
                        this.settlementSummaryFields.status.textContent = status
                            .split(' ')
                            .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
                            .join(' ');
                    }

                    if (this.settlementSummaryFields.whatsapp) {
                        this.settlementSummaryFields.whatsapp.textContent = data?.client_whatsapp || '-';
                    }

                    if (this.settlementSummaryFields.total) {
                        this.settlementSummaryFields.total.textContent = currencyFormatter(data?.total);
                    }

                    if (this.settlementSummaryFields.downPayment) {
                        this.settlementSummaryFields.downPayment.textContent = currencyFormatter(data?.down_payment);
                    }

                    if (this.settlementSummaryFields.remaining) {
                        this.settlementSummaryFields.remaining.textContent = currencyFormatter(data?.remaining_balance);
                    }

                    if (this.settlementSummaryFields.dueDate) {
                        const dueDate = data?.due_date ? new Date(`${data.due_date}T00:00:00`) : null;
                        this.settlementSummaryFields.dueDate.textContent = dueDate && !Number.isNaN(dueDate.getTime())
                            ? dueDate.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })
                            : '-';
                    }

                    if (this.settlementSummaryFields.address) {
                        this.settlementSummaryFields.address.textContent = data?.client_address || '-';
                    }

                    this.settlementSummary.classList.remove('hidden');

                    this.setPriceInputValue(this.settlementRemainingInput, data?.remaining_balance);
                    this.setPriceInputValue(this.settlementPaidInput, data?.remaining_balance);
                },
                showSettlementError(message) {
                    if (this.settlementSummary) {
                        this.settlementSummary.classList.add('hidden');
                    }

                    if (this.settlementError) {
                        this.settlementError.textContent = message;
                        this.settlementError.classList.remove('hidden');
                    }
                },
                clearSettlementFeedback() {
                    if (this.settlementError) {
                        this.settlementError.textContent = '';
                        this.settlementError.classList.add('hidden');
                    }

                    if (this.settlementSummary) {
                        this.settlementSummary.classList.add('hidden');
                    }
                },
                setPriceInputValue(input, value) {
                    if (!input) {
                        return;
                    }

                    const numeric = Number(value);
                    const sanitized = Number.isFinite(numeric) ? Math.max(Math.round(numeric), 0) : 0;
                    const rawString = String(sanitized);

                    input.dataset.rawValue = rawString;
                    input.value = rawString ? rawString.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
                },
                formatPrice(input) {
                    const raw = (input.value || '').replace(/\D/g, '');
                    input.dataset.rawValue = raw;
                    input.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
                },
                formatCurrency(value) {
                    const numeric = Number(value);
                    const resolved = Number.isFinite(numeric) ? numeric : 0;

                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                    }).format(resolved);
                },
                updateTotal() {
                    if (!this.itemsContainer || !this.totalElement) {
                        return;
                    }

                    let total = 0;
                    this.itemsContainer.querySelectorAll('[data-invoice-item]').forEach((item) => {
                        const quantityInput = item.querySelector('[data-role="quantity-input"]');
                        const priceInput = item.querySelector('[data-role="price-input"]');
                        const quantity = parseInt(quantityInput?.value || '0', 10) || 0;
                        const price = parseInt(priceInput?.dataset.rawValue || '0', 10) || 0;
                        total += quantity * price;
                    });

                    this.totalElement.textContent = this.formatCurrency(total || 0);
                },
            };
        };
    </script>
@endonce
