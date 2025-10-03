<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Buat Invoice - Vodeco</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen py-10">
        <div class="max-w-5xl mx-auto px-4">
            <div class="mb-10 text-center">
                <img src="{{ asset('logo-vodeco-dark-mode.png') }}" alt="Logo Vodeco" class="mx-auto h-16">
                <h1 class="mt-4 text-3xl font-semibold text-gray-900">Formulir Pembuatan Invoice</h1>
                <p class="mt-2 text-gray-600">Isi detail berikut untuk membuat invoice. Setelah formulir dikirim, file PDF akan terunduh secara otomatis.</p>
            </div>

            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-8 md:px-10 space-y-8">
                    @if (! $passphraseSession)
                        <div class="space-y-4">
                            <h2 class="text-xl font-semibold text-gray-900">Masukkan Passphrase Akses</h2>
                            <p class="text-gray-600">Untuk alasan keamanan, silakan verifikasi passphrase yang diberikan oleh tim keuangan sebelum mengisi formulir.</p>

                            @if ($errors->hasBag('passphraseVerification'))
                                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                                    <ul class="list-disc pl-5 space-y-1 text-sm">
                                        @foreach ($errors->getBag('passphraseVerification')->all() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('invoices.public.passphrase.verify') }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="passphrase" class="block text-sm font-medium text-gray-700">Passphrase Portal Invoice</label>
                                    <input type="password" name="passphrase" id="passphrase" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required autocomplete="off">
                                </div>
                                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Verifikasi Passphrase</button>
                            </form>
                        </div>
                    @else
                        @php
                            $transactionLabels = [
                                'down_payment' => 'Down Payment',
                                'full_payment' => 'Bayar Lunas',
                                'settlement' => 'Pelunasan',
                            ];

                            $allowedTransactions = array_values(array_intersect(array_keys($transactionLabels), $allowedTransactionTypes));
                            $defaultTransaction = old('transaction_type');

                            if (! $defaultTransaction || ! in_array($defaultTransaction, $allowedTransactions, true)) {
                                $defaultTransaction = $allowedTransactions[0] ?? 'down_payment';
                            }

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

                        <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4 text-indigo-800">
                            <div class="flex flex-col gap-1">
                                <span class="text-sm uppercase tracking-wide">Akses Terverifikasi</span>
                                <span class="text-base font-semibold">{{ $passphraseSession['access_label'] ?? 'Portal Invoice' }}</span>
                                <span class="text-sm text-indigo-600">{{ session('passphrase_verified') ?? 'Anda dapat membuat invoice sesuai izin yang diberikan.' }}</span>
                            </div>
                        </div>

                        @if ($errors->any())
                            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                                <h2 class="mb-2 font-semibold">Terjadi kesalahan:</h2>
                                <ul class="list-disc space-y-1 pl-5 text-sm">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (empty($allowedTransactions))
                            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-800">
                                Passphrase aktif tidak memiliki tipe transaksi yang diizinkan. Hubungi administrator untuk memperbarui konfigurasi.
                            </div>
                        @else
                            <form action="{{ route('invoices.public.store') }}" method="POST" class="space-y-10" id="invoice-form" data-category-options='@json($categoryOptions)' data-default-tab="{{ $defaultTransaction }}"
                                x-data="{
                                    activeTab: '{{ $defaultTransaction }}',
                                    setTab(tab) {
                                        this.activeTab = tab;
                                        window.dispatchEvent(new CustomEvent('transaction-tab-changed', { detail: tab }));
                                    },
                                    tabClass(tab) {
                                        return this.activeTab === tab
                                            ? 'px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white'
                                            : 'px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200';
                                    }
                                }"
                                x-init="window.dispatchEvent(new CustomEvent('transaction-tab-changed', { detail: activeTab }))">
                                @csrf
                                <input type="hidden" name="passphrase_token" value="{{ $passphraseToken }}">
                                <input type="hidden" name="transaction_type" id="transaction_type" x-model="activeTab">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <div>
                                            <label for="client_name" class="block text-sm font-medium text-gray-700">Nama Klien</label>
                                            <input type="text" name="client_name" id="client_name" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ old('client_name') }}" :required="activeTab !== 'settlement'" :disabled="activeTab === 'settlement'">
                                            @error('client_name')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="client_whatsapp" class="block text-sm font-medium text-gray-700">Nomor WhatsApp Klien</label>
                                            <input type="text" name="client_whatsapp" id="client_whatsapp" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ old('client_whatsapp') }}" :required="activeTab !== 'settlement'" :disabled="activeTab === 'settlement'">
                                            @error('client_whatsapp')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="due_date" class="block text-sm font-medium text-gray-700">Tanggal Jatuh Tempo</label>
                                            <input type="date" name="due_date" id="due_date" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ old('due_date') }}" :disabled="activeTab === 'settlement'">
                                            @error('due_date')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div>
                                            <h2 class="text-sm font-medium text-gray-700 mb-2">Jenis Transaksi</h2>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($allowedTransactions as $type)
                                                    <button type="button" @click="setTab('{{ $type }}')" :class="tabClass('{{ $type }}')">
                                                        {{ $transactionLabels[$type] }}
                                                    </button>
                                                @endforeach
                                            </div>

                                            <div class="mt-6 space-y-4">
                                                <div x-show="activeTab !== 'settlement'" x-cloak>
                                                    <label for="customer_service_name" class="block text-sm font-medium text-gray-700">Customer Service</label>
                                                    <input type="text" name="customer_service_name" id="customer_service_name" list="customer-service-options" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ketik nama customer service" value="{{ old('customer_service_name') }}" :required="activeTab !== 'settlement'" :disabled="activeTab === 'settlement'">
                                                    <datalist id="customer-service-options">
                                                        @foreach ($customerServices as $customerService)
                                                            <option value="{{ $customerService->name }}">{{ $customerService->name }}</option>
                                                        @endforeach
                                                    </datalist>
                                                    @error('customer_service_name')
                                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </div>

                                                <div>
                                                    <label for="client_address" class="block text-sm font-medium text-gray-700">Alamat Klien</label>
                                                    <textarea name="client_address" id="client_address" rows="6" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :required="activeTab !== 'settlement'" :disabled="activeTab === 'settlement'">{{ old('client_address') }}</textarea>
                                                    @error('client_address')
                                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div x-show="activeTab !== 'settlement'" x-cloak class="space-y-4">
                                        <div class="flex items-center justify-between">
                                            <h2 class="text-lg font-semibold text-gray-900">Daftar Item</h2>
                                            <button type="button" id="add-item" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                + Tambah Item
                                            </button>
                                        </div>

                                        <div id="invoice-items" class="space-y-4">
                                            @foreach ($oldItems as $index => $item)
                                                <div class="grid grid-cols-12 gap-4 invoice-item bg-gray-50 p-4 rounded-xl">
                                                    <div class="col-span-12 md:col-span-4">
                                                        <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                                        <textarea name="items[{{ $index }}][description]" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 description" required>{{ $item['description'] ?? '' }}</textarea>
                                                        @error('items.' . $index . '.description')
                                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                    <div class="col-span-12 md:col-span-3">
                                                        <label class="block text-sm font-medium text-gray-700">Kategori</label>
                                                        <select name="items[{{ $index }}][category_id]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 category-select" required>
                                                            <option value="">Pilih kategori pemasukan</option>
                                                            @foreach ($incomeCategories as $category)
                                                                <option value="{{ $category->id }}" @selected(($item['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        @error('items.' . $index . '.category_id')
                                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                    <div class="col-span-6 md:col-span-2">
                                                        <label class="block text-sm font-medium text-gray-700">Kuantitas</label>
                                                        <input type="number" name="items[{{ $index }}][quantity]" class="quantity mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ $item['quantity'] ?? 1 }}" min="1" required>
                                                        @error('items.' . $index . '.quantity')
                                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                    <div class="col-span-6 md:col-span-2">
                                                        <label class="block text-sm font-medium text-gray-700">Harga Satuan</label>
                                                        <input type="text" name="items[{{ $index }}][price]" class="price-input mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ $item['price'] ?? '' }}" required>
                                                        @error('items.' . $index . '.price')
                                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                    <div class="col-span-12 md:col-span-1 flex items-end justify-end">
                                                        <button type="button" class="remove-item text-sm font-semibold text-red-600 hover:text-red-700">Hapus</button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div>
                                            <label for="down_payment_due" class="block text-sm font-medium text-gray-700">Rencana Down Payment</label>
                                            <input type="text" name="down_payment_due" id="down_payment_due" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 price-input" value="{{ old('down_payment_due') }}" placeholder="Contoh: 5.000.000" :disabled="activeTab !== 'down_payment'">
                                            <p class="mt-1 text-xs text-gray-500">Opsional. Nilai ini akan diusulkan sebagai nominal pembayaran awal ketika mencatat pembayaran.</p>
                                            @error('down_payment_due')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="flex flex-col items-end gap-2 border-t border-gray-200 pt-6">
                                            <p class="text-lg font-semibold text-gray-900">Total Invoice</p>
                                            <p id="total-amount" class="text-2xl font-bold text-indigo-600">Rp 0</p>
                                        </div>
                                    </div>

                                    <div x-show="activeTab === 'settlement'" x-cloak class="space-y-6">
                                        <div>
                                            <label for="settlement_invoice_number" class="block text-sm font-medium text-gray-700">Nomor Invoice</label>
                                            <input type="text" name="settlement_invoice_number" id="settlement_invoice_number" value="{{ old('settlement_invoice_number') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @error('settlement_invoice_number')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="settlement_remaining_balance" class="block text-sm font-medium text-gray-700">Sisa Tagihan</label>
                                            <input type="text" name="settlement_remaining_balance" id="settlement_remaining_balance" value="{{ old('settlement_remaining_balance') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 price-input" placeholder="Contoh: 2.500.000">
                                            @error('settlement_remaining_balance')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="settlement_paid_amount" class="block text-sm font-medium text-gray-700">Nominal Dibayarkan</label>
                                            <input type="text" name="settlement_paid_amount" id="settlement_paid_amount" value="{{ old('settlement_paid_amount') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 price-input" placeholder="Contoh: 1.500.000">
                                            @error('settlement_paid_amount')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <span class="block text-sm font-medium text-gray-700 mb-2">Status Pelunasan</span>
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-6">
                                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="radio" name="settlement_payment_status" value="paid_full" @checked(old('settlement_payment_status') === 'paid_full')>
                                                    <span>Bayar Lunas</span>
                                                </label>
                                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="radio" name="settlement_payment_status" value="paid_partial" @checked(old('settlement_payment_status') === 'paid_partial')>
                                                    <span>Bayar Sebagian</span>
                                                </label>
                                            </div>
                                            @error('settlement_payment_status')
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-green-600 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                        Buat & Unduh Invoice
                                    </button>
                                </div>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            <p class="mt-8 text-center text-sm text-gray-500">
                Setelah invoice dibuat, Anda juga dapat membagikan link publik kepada klien menggunakan token yang tersedia di dashboard customer service.
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const invoiceForm = document.getElementById('invoice-form');

            if (!invoiceForm) {
                return;
            }

            const categoryOptions = JSON.parse(invoiceForm.dataset.categoryOptions || '[]');
            let itemIndex = invoiceForm.querySelectorAll('#invoice-items .invoice-item').length;
            const invoiceItemsContainer = document.getElementById('invoice-items');
            const totalAmountElement = document.getElementById('total-amount');
            const addItemButton = document.getElementById('add-item');
            const transactionTypeInput = document.getElementById('transaction_type');

            function formatPrice(input) {
                const raw = (input.value || '').replace(/\D/g, '');
                input.dataset.rawValue = raw;
                input.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
            }

            function updateTotal() {
                if (!invoiceItemsContainer || !totalAmountElement) {
                    return;
                }

                let total = 0;
                invoiceItemsContainer.querySelectorAll('.invoice-item').forEach((item) => {
                    const quantity = parseInt(item.querySelector('.quantity')?.value || '0', 10) || 0;
                    const price = parseInt(item.querySelector('.price-input')?.dataset.rawValue || '0', 10) || 0;
                    total += quantity * price;
                });

                totalAmountElement.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                }).format(total || 0);
            }

            function applyTransactionScope(transactionType) {
                const scope = transactionType || 'down_payment';

                if (addItemButton) {
                    const isSettlement = scope === 'settlement';
                    addItemButton.disabled = isSettlement;
                    addItemButton.classList.toggle('opacity-50', isSettlement);
                    addItemButton.classList.toggle('cursor-not-allowed', isSettlement);
                }

                if (invoiceItemsContainer) {
                    invoiceItemsContainer.querySelectorAll('.category-select, .quantity, .price-input, .description').forEach((element) => {
                        element.disabled = scope === 'settlement';
                    });
                }
            }

            document.querySelectorAll('.price-input').forEach((input) => {
                formatPrice(input);
                input.addEventListener('input', () => {
                    formatPrice(input);
                    updateTotal();
                });
                input.addEventListener('blur', () => formatPrice(input));
            });

            document.querySelectorAll('.quantity').forEach((input) => {
                input.addEventListener('input', updateTotal);
            });

            if (addItemButton && invoiceItemsContainer) {
                addItemButton.addEventListener('click', () => {
                    const template = document.createElement('template');
                    template.innerHTML = `
                        <div class="grid grid-cols-12 gap-4 invoice-item bg-gray-50 p-4 rounded-xl">
                            <div class="col-span-12 md:col-span-4">
                                <textarea name="items[${itemIndex}][description]" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 description" placeholder="Deskripsi" required></textarea>
                            </div>
                            <div class="col-span-12 md:col-span-3">
                                <select name="items[${itemIndex}][category_id]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 category-select" required>
                                    ${['<option value="">Pilih kategori pemasukan</option>'].concat(categoryOptions.map((option) => `<option value="${option.id}">${option.name}</option>`)).join('')}
                                </select>
                            </div>
                            <div class="col-span-6 md:col-span-2">
                                <input type="number" name="items[${itemIndex}][quantity]" class="quantity mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="1" min="1" required>
                            </div>
                            <div class="col-span-6 md:col-span-2">
                                <input type="text" name="items[${itemIndex}][price]" class="price-input mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Harga" required>
                            </div>
                            <div class="col-span-12 md:col-span-1 flex items-end justify-end">
                                <button type="button" class="remove-item text-sm font-semibold text-red-600 hover:text-red-700">Hapus</button>
                            </div>
                        </div>
                    `.trim();

                    const element = template.content.firstChild;
                    invoiceItemsContainer.appendChild(element);

                    element.querySelectorAll('.price-input').forEach((input) => {
                        formatPrice(input);
                        input.addEventListener('input', () => {
                            formatPrice(input);
                            updateTotal();
                        });
                        input.addEventListener('blur', () => formatPrice(input));
                    });

                    element.querySelectorAll('.quantity').forEach((input) => {
                        input.addEventListener('input', updateTotal);
                    });

                    itemIndex++;
                    updateTotal();
                });
            }

            if (invoiceItemsContainer) {
                invoiceItemsContainer.addEventListener('click', (event) => {
                    if (event.target.classList.contains('remove-item')) {
                        const item = event.target.closest('.invoice-item');
                        if (item) {
                            item.remove();
                            updateTotal();
                        }
                    }
                });
            }

            invoiceForm.addEventListener('submit', () => {
                document.querySelectorAll('.price-input').forEach((input) => {
                    input.value = input.dataset.rawValue || '';
                });
            });

            window.addEventListener('transaction-tab-changed', (event) => {
                applyTransactionScope(event.detail);
            });

            applyTransactionScope(transactionTypeInput?.value || invoiceForm.dataset.defaultTab || 'down_payment');
            updateTotal();
        });
    </script>
</body>
</html>
