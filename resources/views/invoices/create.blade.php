<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Buat Invoice Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @php
                    $oldItems = old('items', [[
                        'description' => '',
                        'quantity' => 1,
                        'price' => '',
                        'category_id' => optional($incomeCategories->first())->id,
                    ]]);
                    $categoryOptions = $incomeCategories->map(fn ($category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ])->values();
                @endphp
                <form action="{{ route('invoices.store') }}" method="POST" class="p-6" id="invoice-form" data-category-options='@json($categoryOptions)'>
                    @csrf

                    {{-- Customer Service --}}
                    <div class="mb-6">
                        <label for="customer_service_id" class="block text-sm font-medium text-gray-700">Customer Service</label>
                        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                            <select name="customer_service_id" id="customer_service_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Pilih customer service</option>
                                @foreach($customerServices as $customerService)
                                    <option value="{{ $customerService->id }}" @selected(old('customer_service_id') == $customerService->id)>
                                        {{ $customerService->name }}
                                    </option>
                                @endforeach
                            </select>
                            <a href="{{ route('customer-services.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                Tambah Customer Service
                            </a>
                        </div>
                        @error('customer_service_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Informasi Klien --}}
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Informasi Klien</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="client_name" class="block text-sm font-medium text-gray-700">Nama Klien</label>
                            <input type="text" name="client_name" id="client_name" value="{{ old('client_name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            @error('client_name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="client_whatsapp" class="block text-sm font-medium text-gray-700">Nomor WhatsApp Klien</label>
                            <input type="text" name="client_whatsapp" id="client_whatsapp" value="{{ old('client_whatsapp') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            @error('client_whatsapp')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="client_address" class="block text-sm font-medium text-gray-700">Alamat Klien</label>
                            <textarea name="client_address" id="client_address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>{{ old('client_address') }}</textarea>
                            @error('client_address')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Informasi Invoice --}}
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Detail Invoice</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-700">Tanggal Jatuh Tempo</label>
                            <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @error('due_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Item Invoice --}}
                    <div x-data="{
                        activeTab: '{{ old('transaction_type', 'down_payment') }}',
                        setTab(tab) {
                            this.activeTab = tab;
                            window.dispatchEvent(new CustomEvent('transaction-tab-changed', { detail: tab }));
                        },
                        tabClass(tab) {
                            return this.activeTab === tab
                                ? 'px-4 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white'
                                : 'px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200';
                        }
                    }" x-init="window.dispatchEvent(new CustomEvent('transaction-tab-changed', { detail: activeTab }))" class="mt-8">
                        <input type="hidden" name="transaction_type" id="transaction_type" x-model="activeTab">

                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Jenis Transaksi</h3>
                        <div class="flex flex-wrap gap-3">
                            <button type="button" :class="tabClass('down_payment')" @click="setTab('down_payment')">Down Payment</button>
                            <button type="button" :class="tabClass('full_payment')" @click="setTab('full_payment')">Bayar Lunas</button>
                            <button type="button" :class="tabClass('settlement')" @click="setTab('settlement')">Pelunasan</button>
                        </div>

                        <div class="mt-6" x-show="activeTab === 'down_payment'" x-cloak>
                            <label for="down_payment_due" class="block text-sm font-medium text-gray-700">Nominal Down Payment</label>
                            <input type="text" name="down_payment_due" id="down_payment_due" value="{{ old('down_payment_due') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm price-input" placeholder="Contoh: 5.000.000" data-transaction-scope="down-payment" :required="activeTab === 'down_payment'" :disabled="activeTab !== 'down_payment'">
                            <p class="mt-1 text-xs text-gray-500">Nominal DP wajib diisi untuk transaksi Down Payment.</p>
                            @error('down_payment_due')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-6" x-show="activeTab !== 'settlement'" x-cloak>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Item</h3>
                            <div id="invoice-items" class="space-y-4">
                                @foreach($oldItems as $index => $item)
                                    <div class="grid grid-cols-12 gap-4 invoice-item bg-gray-50 p-4 rounded-lg">
                                        <div class="col-span-12">
                                            <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                            <textarea name="items[{{ $index }}][description]" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm description" data-transaction-scope="line-item" required>{{ $item['description'] ?? '' }}</textarea>
                                            @error('items.' . $index . '.description')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="col-span-12 md:col-span-4">
                                            <label class="block text-sm font-medium text-gray-700">Kategori</label>
                                            <select name="items[{{ $index }}][category_id]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm category-select" data-transaction-scope="line-item" required>
                                                <option value="">Pilih kategori pemasukan</option>
                                                @foreach($incomeCategories as $category)
                                                    <option value="{{ $category->id }}" @selected(($item['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('items.' . $index . '.category_id')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="col-span-6 md:col-span-3">
                                            <label class="block text-sm font-medium text-gray-700">Kuantitas</label>
                                            <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm quantity" data-transaction-scope="line-item" min="1" required>
                                            @error('items.' . $index . '.quantity')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="col-span-6 md:col-span-3">
                                            <label class="block text-sm font-medium text-gray-700">Harga Satuan</label>
                                            <input type="text" name="items[{{ $index }}][price]" value="{{ $item['price'] ?? '' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm price-input" data-transaction-scope="line-item" required>
                                            @error('items.' . $index . '.price')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="col-span-12 md:col-span-2 flex items-end justify-end">
                                            <button type="button" class="text-red-500 hover:text-red-700 remove-item">Hapus</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <button type="button" id="add-item" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Tambah Item</button>
                            </div>
                        </div>

                        <div class="mt-6" x-show="activeTab === 'settlement'" x-cloak>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Detail Pelunasan</h3>
                            <div class="space-y-6">
                                <div>
                                    <label for="settlement_invoice_number" class="block text-sm font-medium text-gray-700">Nomor Invoice</label>
                                    <input type="text" name="settlement_invoice_number" id="settlement_invoice_number" value="{{ old('settlement_invoice_number') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" data-transaction-scope="settlement" data-settlement-required="true">
                                    @error('settlement_invoice_number')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="settlement_remaining_balance" class="block text-sm font-medium text-gray-700">Sisa Tagihan</label>
                                    <input type="text" name="settlement_remaining_balance" id="settlement_remaining_balance" value="{{ old('settlement_remaining_balance') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm price-input" placeholder="Contoh: 2.500.000" data-transaction-scope="settlement" data-settlement-required="true">
                                    @error('settlement_remaining_balance')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="settlement_paid_amount" class="block text-sm font-medium text-gray-700">Nominal Dibayarkan</label>
                                    <input type="text" name="settlement_paid_amount" id="settlement_paid_amount" value="{{ old('settlement_paid_amount') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm price-input" placeholder="Contoh: 1.500.000" data-transaction-scope="settlement" data-settlement-required="true">
                                    @error('settlement_paid_amount')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-700 mb-2">Status Pelunasan</span>
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-6">
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input type="radio" name="settlement_payment_status" value="paid_full" @checked(old('settlement_payment_status') === 'paid_full') data-transaction-scope="settlement" data-settlement-required="true">
                                            <span>Bayar Lunas</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input type="radio" name="settlement_payment_status" value="paid_partial" @checked(old('settlement_payment_status') === 'paid_partial') data-transaction-scope="settlement" data-settlement-required="true">
                                            <span>Bayar Sebagian</span>
                                        </label>
                                    </div>
                                    @error('settlement_payment_status')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-200 flex justify-end" x-show="activeTab !== 'settlement'" x-cloak>
                            <div class="text-right">
                                <p class="text-lg font-medium text-gray-900 dark:text-white">Total: <span id="total-amount">Rp 0</span></p>
                            </div>
                        </div>
                    </div>


                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="px-6 py-3 text-base font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Simpan Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const invoiceForm = document.getElementById('invoice-form');
            const categoryOptions = JSON.parse(invoiceForm.dataset.categoryOptions || '[]');
            let itemIndex = {{ count($oldItems) }};

            const invoiceItemsContainer = document.getElementById('invoice-items');
            const totalAmountElement = document.getElementById('total-amount');
            const addItemButton = document.getElementById('add-item');
            const transactionTypeInput = document.getElementById('transaction_type');

            function applyTransactionScope(transactionType) {
                const scope = transactionType || 'down_payment';

                invoiceForm.querySelectorAll('[data-transaction-scope]').forEach(function (input) {
                    const targetScope = input.dataset.transactionScope;

                    if (targetScope === 'settlement') {
                        const enable = scope === 'settlement';
                        input.disabled = !enable;
                        if (input.dataset.settlementRequired === 'true') {
                            input.required = enable;
                        }
                        if (!enable && input.type === 'radio') {
                            input.checked = false;
                        }
                    } else if (targetScope === 'down-payment') {
                        const enable = scope === 'down_payment';
                        input.disabled = !enable;
                        input.required = enable;
                    } else if (targetScope === 'line-item') {
                        input.disabled = scope === 'settlement';
                    }
                });

                if (addItemButton) {
                    const isDisabled = scope === 'settlement';
                    addItemButton.disabled = isDisabled;
                    addItemButton.classList.toggle('opacity-50', isDisabled);
                    addItemButton.classList.toggle('cursor-not-allowed', isDisabled);
                }
            }

            function formatPrice(input) {
                const raw = (input.value || '').replace(/\D/g, '');
                input.dataset.rawValue = raw;
                input.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
            }

            function initPriceInput(input) {
                formatPrice(input);
                input.addEventListener('input', function () {
                    formatPrice(input);
                    updateTotal();
                });
                input.addEventListener('blur', function () {
                    formatPrice(input);
                });
            }

            function updateTotal() {
                if (!invoiceItemsContainer || !totalAmountElement) {
                    return;
                }
                let total = 0;
                document.querySelectorAll('#invoice-items .invoice-item').forEach(function (item) {
                    const quantityValue = item.querySelector('.quantity')?.value || '0';
                    const priceInput = item.querySelector('.price-input');
                    const quantity = parseInt(quantityValue, 10) || 0;
                    const price = parseInt(priceInput?.dataset.rawValue || '0', 10) || 0;
                    total += quantity * price;
                });

                totalAmountElement.textContent = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(total || 0);
            }

            function buildCategoryOptions(selected = '') {
                return ['<option value="">Pilih kategori pemasukan</option>'].concat(
                    categoryOptions.map(function (option) {
                        const isSelected = String(option.id) === String(selected);
                        return `<option value="${option.id}"${isSelected ? ' selected' : ''}>${option.name}</option>`;
                    })
                ).join('');
            }

            document.querySelectorAll('.price-input').forEach(initPriceInput);
            document.querySelectorAll('.quantity').forEach(function (input) {
                input.addEventListener('input', updateTotal);
            });

            if (addItemButton && invoiceItemsContainer) {
                addItemButton.addEventListener('click', function () {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'grid grid-cols-12 gap-4 invoice-item bg-gray-50 p-4 rounded-lg';
                    wrapper.innerHTML = `
                        <div class="col-span-12">
                            <textarea name="items[${itemIndex}][description]" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm description" placeholder="Deskripsi" data-transaction-scope="line-item" required></textarea>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <select name="items[${itemIndex}][category_id]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm category-select" data-transaction-scope="line-item" required>
                                ${buildCategoryOptions(categoryOptions[0]?.id ?? '')}
                            </select>
                        </div>
                        <div class="col-span-6 md:col-span-3">
                            <input type="number" name="items[${itemIndex}][quantity]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm quantity" data-transaction-scope="line-item" value="1" min="1" required>
                        </div>
                        <div class="col-span-6 md:col-span-3">
                            <input type="text" name="items[${itemIndex}][price]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm price-input" data-transaction-scope="line-item" required>
                        </div>
                        <div class="col-span-12 md:col-span-2 flex items-end justify-end">
                            <button type="button" class="text-red-500 hover:text-red-700 remove-item">Hapus</button>
                        </div>
                    `;

                    invoiceItemsContainer.appendChild(wrapper);

                    wrapper.querySelectorAll('.price-input').forEach(initPriceInput);
                    wrapper.querySelectorAll('.quantity').forEach(function (input) {
                        input.addEventListener('input', updateTotal);
                    });

                    itemIndex++;
                    applyTransactionScope(transactionTypeInput?.value || 'down_payment');
                    updateTotal();
                });

                invoiceItemsContainer.addEventListener('click', function (event) {
                    if (event.target.classList.contains('remove-item')) {
                        const item = event.target.closest('.invoice-item');
                        if (item) {
                            item.remove();
                            updateTotal();
                        }
                    }
                });
            }

            invoiceForm.addEventListener('submit', function () {
                const scope = transactionTypeInput?.value || 'down_payment';
                applyTransactionScope(scope);
                document.querySelectorAll('.price-input').forEach(function (input) {
                    if (input.disabled) {
                        return;
                    }
                    const raw = input.dataset.rawValue || '';
                    input.value = raw;
                });
            });

            window.addEventListener('transaction-tab-changed', function (event) {
                applyTransactionScope(event.detail);
                updateTotal();
            });

            applyTransactionScope(transactionTypeInput?.value || 'down_payment');
            updateTotal();
        });
    </script>
    @endpush
</x-app-layout>
