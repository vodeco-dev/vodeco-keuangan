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
                <p class="mt-2 text-gray-600">Isi detail berikut untuk membuat invoice. Setelah formulir dikirim, tautan publik untuk mengakses PDF akan dibuat.</p>
            </div>

            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-8 md:px-10 space-y-8">
                    @if (session('invoice_pdf_url'))
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                            <p class="font-semibold">{{ session('status') ?: 'Invoice berhasil dibuat.' }}</p>
                            <p class="mt-2">Nomor Invoice Anda adalah: <span class="font-mono font-bold">{{ session('invoice_number') }}</span></p>
                            <div class="mt-4">
                                <a href="{{ session('invoice_pdf_url') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-green-700">
                                    Buka PDF Invoice
                                </a>
                                <button type="button" x-data="{ copied: false }" @click="navigator.clipboard.writeText('{{ session('invoice_pdf_url') }}'); copied = true; setTimeout(() => copied = false, 2000);" class="ml-2 inline-flex items-center rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 shadow hover:bg-gray-300">
                                    <span x-show="!copied">Salin Tautan PDF</span>
                                    <span x-show="copied" x-cloak>Tautan disalin!</span>
                                </button>
                            </div>
                            <p class="mt-3 text-xs text-green-700">Anda dapat menyimpan tautan ini untuk mengakses invoice di kemudian hari.</p>
                        </div>
                    @elseif (session('status'))
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif
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
                            $allowedTransactions = array_values(array_intersect(['down_payment', 'full_payment', 'pass_through', 'settlement'], $allowedTransactionTypes));
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

                            $allowedPortalTabs = ['create_invoice', 'confirm_payment'];
                            
                            // Tambahkan tab pelunasan untuk Admin Pelunasan
                            if ($passphraseSession && ($passphraseSession['access_type'] ?? '') === 'admin_pelunasan') {
                                $allowedPortalTabs[] = 'settlement_lookup';
                            }
                            
                            $activePortalTab = old('portal_mode') ?: session('active_portal_tab', 'create_invoice');

                            if (! in_array($activePortalTab, $allowedPortalTabs, true)) {
                                $activePortalTab = 'create_invoice';
                            }

                            $passThroughPackagesCollection = ($passThroughPackages ?? collect())->map(function ($package) {
                                return [
                                    'id' => $package->id,
                                    'name' => $package->name,
                                    'customer_type' => $package->customerType,
                                    'customer_label' => $package->customerLabel(),
                                    'daily_balance' => $package->dailyBalance,
                                    'duration_days' => $package->durationDays,
                                    'maintenance_fee' => $package->maintenanceFee,
                                    'account_creation_fee' => $package->accountCreationFee,
                                ];
                            });
                            $firstPassThroughPackage = $passThroughPackagesCollection->first();
                            $defaultPassThroughPackageId = old('pass_through_package_id', $firstPassThroughPackage['id'] ?? null);
                            $defaultPassThroughQuantity = old('pass_through_quantity', 1);

                            $passThroughConfig = [
                                'packages' => $passThroughPackagesCollection->values(),
                                'defaults' => [
                                    'packageId' => $defaultPassThroughPackageId,
                                    'quantity' => (int) $defaultPassThroughQuantity,
                                    'adBudgetTotal' => old('pass_through_ad_budget_total'),
                                    'maintenanceTotal' => old('pass_through_maintenance_total'),
                                    'accountCreationTotal' => old('pass_through_account_creation_total'),
                                    'totalPrice' => old('pass_through_total_price'),
                                    'custom' => [
                                        'customerType' => old('pass_through_custom_customer_type', 'new'),
                                        'dailyBalance' => old('pass_through_custom_daily_balance'),
                                        'durationDays' => old('pass_through_custom_duration_days'),
                                        'maintenanceFee' => old('pass_through_custom_maintenance_fee'),
                                        'accountCreationFee' => old('pass_through_custom_account_creation_fee'),
                                    ],
                                    'units' => [
                                        'dailyBalance' => old('pass_through_daily_balance_unit'),
                                        'adBudget' => old('pass_through_ad_budget_unit'),
                                        'maintenance' => old('pass_through_maintenance_unit'),
                                        'accountCreation' => old('pass_through_account_creation_unit'),
                                    ],
                                    'totals' => [
                                        'adBudget' => old('pass_through_ad_budget_total'),
                                        'maintenance' => old('pass_through_maintenance_total'),
                                        'accountCreation' => old('pass_through_account_creation_total'),
                                        'overall' => old('pass_through_total_price'),
                                        'dailyBalance' => old('pass_through_daily_balance_total'),
                                    ],
                                    'durationDays' => old('pass_through_duration_days'),
                                ],
                            ];
                        @endphp

                        <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4 text-indigo-800">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm uppercase tracking-wide">Akses Terverifikasi</span>
                                    <span class="text-base font-semibold">{{ $passphraseSession['display_label'] ?? $passphraseSession['access_label'] ?? 'Portal Invoice' }}</span>
                                    <span class="text-sm text-indigo-600">{{ session('passphrase_verified') ?? 'Anda dapat membuat invoice sesuai izin yang diberikan.' }}</span>
                                </div>
                                <form method="POST" action="{{ route('invoices.public.passphrase.logout') }}" class="flex-shrink-0">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center rounded-lg border border-indigo-200 bg-white/80 px-3 py-1.5 text-sm font-medium text-indigo-700 shadow-sm hover:bg-white">Keluar dari Sesi</button>
                                </form>
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

                        <div x-data="{ activePortalTab: '{{ $activePortalTab }}' }">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="flex items-center gap-2 rounded-xl bg-gray-100 p-1 flex-wrap">
                                    <button type="button"
                                        class="flex-1 rounded-lg px-4 py-2 text-sm font-semibold transition"
                                        :class="activePortalTab === 'create_invoice' ? 'bg-white text-indigo-600 shadow' : 'text-gray-600 hover:text-indigo-600'"
                                        @click="activePortalTab = 'create_invoice'">
                                        Buat Invoice
                                    </button>
                                    <button type="button"
                                        class="flex-1 rounded-lg px-4 py-2 text-sm font-semibold transition"
                                        :class="activePortalTab === 'confirm_payment' ? 'bg-white text-indigo-600 shadow' : 'text-gray-600 hover:text-indigo-600'"
                                        @click="activePortalTab = 'confirm_payment'">
                                        Konfirmasi Pembayaran
                                    </button>
                                    @if(in_array('settlement_lookup', $allowedPortalTabs))
                                    <button type="button"
                                        class="flex-1 rounded-lg px-4 py-2 text-sm font-semibold transition"
                                        :class="activePortalTab === 'settlement_lookup' ? 'bg-white text-green-600 shadow' : 'text-gray-600 hover:text-green-600'"
                                        @click="activePortalTab = 'settlement_lookup'">
                                        Pelunasan
                                    </button>
                                    @endif
                                    <a href="{{ route('invoices.public.check-confirmation') }}"
                                        class="flex-1 rounded-lg px-4 py-2 text-sm font-semibold text-center transition bg-blue-600 text-white shadow hover:bg-blue-700">
                                        Cek Konfirmasi
                                    </a>
                                </div>
                                <p class="text-xs text-gray-500 md:text-sm" x-show="activePortalTab === 'confirm_payment'" x-cloak>
                                    Unggah bukti pembayaran dengan menyertakan nomor invoice yang telah diterbitkan.
                                </p>
                                <p class="text-xs text-gray-500 md:text-sm" x-show="activePortalTab === 'settlement_lookup'" x-cloak>
                                    Cari invoice untuk mendapatkan link pelunasan yang dapat dibagikan kepada klien.
                                </p>
                            </div>

                            <div class="mt-8 space-y-10">
                                <div x-show="activePortalTab === 'create_invoice'" x-cloak>
                                    @if (empty($allowedTransactions))
                                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-800">
                                            Passphrase aktif tidak memiliki tipe transaksi yang diizinkan. Hubungi administrator untuk memperbarui konfigurasi.
                                        </div>
                                    @else
                                        <form action="{{ route('invoices.public.store') }}" method="POST" class="space-y-10" id="invoice-form"
                                            x-data="invoicePortalForm()">
                                            @csrf
                                            <input type="hidden" name="passphrase_token" value="{{ $passphraseToken }}">
                                            <input type="hidden" name="portal_mode" value="create_invoice">

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div class="space-y-4">
                                                    <div>
                                                        <label for="client_name" class="block text-sm font-medium text-gray-700">Nama Klien</label>
                                                        <input type="text" name="client_name" id="client_name" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ old('client_name') }}" :required="activeTab !== 'settlement'" :disabled="activeTab === 'settlement'">
                                                    </div>
                                                    <div>
                                                        <label for="client_whatsapp" class="block text-sm font-medium text-gray-700">Nomor WhatsApp Klien</label>
                                                        <input type="text" name="client_whatsapp" id="client_whatsapp" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ old('client_whatsapp') }}" :required="activeTab !== 'settlement'" :disabled="activeTab === 'settlement'">
                                                    </div>
                                                    <div>
                                                        <label for="due_date" class="block text-sm font-medium text-gray-700">Tanggal Jatuh Tempo</label>
                                                        <input type="date" name="due_date" id="due_date" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ old('due_date') }}" :disabled="activeTab === 'settlement'">
                                                    </div>
                                                </div>
                                                <div class="space-y-4">
                                                    <div>
                                                        <label for="client_address" class="block text-sm font-medium text-gray-700">Alamat Klien</label>
                                                        <textarea name="client_address" id="client_address" rows="6" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :required="activeTab !== 'settlement'" :disabled="activeTab === 'settlement'">{{ old('client_address') }}</textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <x-invoice.transaction-tabs
                                                id="public-invoice-tabs"
                                                form-id="invoice-form"
                                                :items="$oldItems"
                                                :category-options="$categoryOptions"
                                                :allowed-transactions="$allowedTransactions"
                                                :default-transaction="$defaultTransaction"
                                                :pass-through-packages="$passThroughPackagesCollection"
                                                :pass-through-config="$passThroughConfig"
                                                variant="public"
                                                down-payment-field-label="Rencana Down Payment"
                                                down-payment-placeholder="Contoh: 5.000.000"
                                                down-payment-help="Opsional. Nilai ini akan diusulkan sebagai nominal pembayaran awal ketika mencatat pembayaran."
                                                :down-payment-required="false"
                                                add-item-button-label="+ Tambah Item"
                                                total-label="Total Invoice"
                                                :down-payment-value="old('down_payment_due')"
                                                :settlement-invoice-number="old('settlement_invoice_number')"
                                                :settlement-remaining-balance="old('settlement_remaining_balance')"
                                                :settlement-paid-amount="old('settlement_paid_amount')"
                                                :settlement-payment-status="old('settlement_payment_status')"
                                                data-reference-url-template="{{ route('invoices.public.reference', ['number' => '__NUMBER__'], false) }}"
                                                x-ref="transactionTabs"
                                            />

                                            <div class="flex justify-end">
                                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-green-600 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                                    Buat Invoice
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                </div>

                                <div x-show="activePortalTab === 'confirm_payment'" x-cloak>
                                    @php
                                        $confirmedInvoiceSummary = session('confirmed_invoice_summary');
                                        $initialInvoiceNumber = old('invoice_number', $confirmedInvoiceSummary['number'] ?? '');
                                    @endphp

                                    <form
                                        action="{{ route('invoices.public.payment-confirm') }}"
                                        method="POST"
                                        class="space-y-6"
                                        enctype="multipart/form-data"
                                    >
                                        @csrf
                                        <input type="hidden" name="passphrase_token" value="{{ $passphraseToken }}">
                                        <input type="hidden" name="portal_mode" value="confirm_payment">

                                        <div class="space-y-3">
                                            <h2 class="text-lg font-semibold text-gray-900">Unggah Bukti Pembayaran</h2>
                                            <p class="text-sm text-gray-600">Masukkan nomor invoice yang telah dibuat kemudian unggah bukti pembayaran dalam format PNG atau JPG.</p>
                                        </div>

                                        <div class="space-y-4">
                                            <div>
                                                <label for="invoice_number" class="block text-sm font-medium text-gray-700">Nomor Invoice</label>
                                                <input
                                                    type="text"
                                                    name="invoice_number"
                                                    id="invoice_number"
                                                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    placeholder="Masukkan nomor invoice"
                                                    autocomplete="off"
                                                    value="{{ $initialInvoiceNumber }}"
                                                    required
                                                >
                                                @error('invoice_number')
                                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <label for="payment_proof" class="block text-sm font-medium text-gray-700">Bukti Pembayaran (PNG atau JPG)</label>
                                                <input type="file" name="payment_proof" id="payment_proof" accept="image/png,image/jpeg" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                                <p class="mt-1 text-xs text-gray-500">Pastikan bukti terlihat jelas. File maksimal 5MB.</p>
                                                @error('payment_proof')
                                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                        </div>

                                        <div class="flex justify-end">
                                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                Kirim Bukti Pembayaran
                                            </button>
                                        </div>
                                    </form>

                                    @if ($confirmedInvoiceSummary)
                                        @php
                                            $statusLabels = [
                                                'lunas' => 'Lunas',
                                                'belum lunas' => 'Belum Lunas',
                                            ];

                                            $formatCurrency = static fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
                                        @endphp

                                        <div class="mt-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                                            <h3 class="text-base font-semibold text-green-900">Ringkasan Invoice</h3>
                                            <dl class="mt-3 space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <dt class="font-medium text-green-900">Nomor</dt>
                                                    <dd class="text-green-900">{{ $confirmedInvoiceSummary['number'] ?? '-' }}</dd>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <dt class="font-medium text-green-900">Customer Service</dt>
                                                    <dd class="text-green-900">{{ $confirmedInvoiceSummary['customer_service_name'] ?? '-' }}</dd>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <dt class="font-medium text-green-900">Klien</dt>
                                                    <dd class="text-green-900">{{ $confirmedInvoiceSummary['client_name'] ?? '-' }}</dd>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <dt class="font-medium text-green-900">Total Tagihan</dt>
                                                    <dd class="text-green-900">{{ $formatCurrency($confirmedInvoiceSummary['total'] ?? 0) }}</dd>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <dt class="font-medium text-green-900">Pembayaran Masuk</dt>
                                                    <dd class="text-green-900">{{ $formatCurrency($confirmedInvoiceSummary['down_payment'] ?? 0) }}</dd>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <dt class="font-medium text-green-900">Sisa Tagihan</dt>
                                                    <dd class="text-green-900">{{ $formatCurrency($confirmedInvoiceSummary['remaining_balance'] ?? 0) }}</dd>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <dt class="font-medium text-green-900">Status</dt>
                                                    <dd class="text-green-900">{{ $statusLabels[$confirmedInvoiceSummary['status'] ?? ''] ?? 'Belum Bayar' }}</dd>
                                                </div>
                                            </dl>
                                        </div>
                                    @endif
                                </div>

                                @if(in_array('settlement_lookup', $allowedPortalTabs))
                                <div x-show="activePortalTab === 'settlement_lookup'" x-cloak>
                                    <div class="space-y-6">
                                        <h2 class="text-lg font-semibold text-gray-900">Cari Invoice untuk Pelunasan</h2>
                                        <p class="text-sm text-gray-600">Masukkan nomor invoice untuk mendapatkan link pelunasan yang dapat dibagikan kepada klien.</p>

                                        @if (session('settlement_error'))
                                            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                                                <div class="flex items-center">
                                                    <span class="text-xl mr-3">❌</span>
                                                    <span>{{ session('settlement_error') }}</span>
                                                </div>
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('invoices.public.search-settlement') }}" class="space-y-4">
                                            @csrf
                                            <input type="hidden" name="passphrase_token" value="{{ $passphraseToken }}">
                                            
                                            <div>
                                                <label for="settlement_invoice_number" class="block text-sm font-medium text-gray-700">Nomor Invoice</label>
                                                <input 
                                                    type="text" 
                                                    name="invoice_number" 
                                                    id="settlement_invoice_number" 
                                                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-lg"
                                                    placeholder="Contoh: INV-2024-001"
                                                    value="{{ old('invoice_number') }}"
                                                    required
                                                >
                                                @error('invoice_number')
                                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <button 
                                                type="submit" 
                                                class="inline-flex items-center justify-center rounded-xl bg-green-600 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                            >
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                </svg>
                                                Cari Invoice
                                            </button>
                                        </form>

                                        @if (session('settlement_invoice'))
                                            @php
                                                $settlementInvoice = session('settlement_invoice');
                                                $formatCurrency = static fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
                                            @endphp

                                            <div class="mt-8 border-t border-gray-200 pt-8">
                                                <h3 class="text-lg font-semibold text-gray-900 mb-6">Informasi Invoice</h3>

                                                <!-- Detail Invoice -->
                                                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <div>
                                                            <p class="text-sm text-gray-600">Nomor Invoice</p>
                                                            <p class="font-semibold text-gray-900">{{ $settlementInvoice['number'] }}</p>
                                                        </div>
                                                        <div>
                                                            <p class="text-sm text-gray-600">Nama Klien</p>
                                                            <p class="font-semibold text-gray-900">{{ $settlementInvoice['client_name'] }}</p>
                                                        </div>
                                                        @if($settlementInvoice['client_whatsapp'])
                                                        <div>
                                                            <p class="text-sm text-gray-600">WhatsApp Klien</p>
                                                            <p class="font-semibold text-gray-900">{{ $settlementInvoice['client_whatsapp'] }}</p>
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <p class="text-sm text-gray-600">Total Invoice</p>
                                                            <p class="font-semibold text-gray-900">{{ $formatCurrency($settlementInvoice['total'] ?? 0) }}</p>
                                                        </div>
                                                        @if($settlementInvoice['down_payment'])
                                                        <div>
                                                            <p class="text-sm text-gray-600">Pembayaran Masuk</p>
                                                            <p class="font-semibold text-gray-900">{{ $formatCurrency($settlementInvoice['down_payment'] ?? 0) }}</p>
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <p class="text-sm text-gray-600">Sisa Tagihan</p>
                                                            <p class="font-semibold text-green-600 text-lg">{{ $formatCurrency($settlementInvoice['remaining_balance'] ?? 0) }}</p>
                                                        </div>
                                                        <div>
                                                            <p class="text-sm text-gray-600">Status</p>
                                                            <p class="font-semibold text-gray-900">{{ ucfirst($settlementInvoice['status']) }}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Link Pelunasan -->
                                                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                                                    <h4 class="font-semibold text-green-900 mb-4 flex items-center">
                                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                                        </svg>
                                                        Link Pelunasan Invoice
                                                    </h4>
                                                    
                                                    <div class="bg-white rounded-lg p-4 mb-4 break-all">
                                                        <p class="text-sm text-gray-900 font-mono">{{ $settlementInvoice['settlement_url'] }}</p>
                                                    </div>

                                                    @if($settlementInvoice['settlement_token_expires_at'])
                                                    <p class="text-sm text-green-700 mb-4">
                                                        <strong>Berlaku sampai:</strong> {{ $settlementInvoice['settlement_token_expires_at'] }} WIB
                                                    </p>
                                                    @endif

                                                    <div class="flex flex-col sm:flex-row gap-3">
                                                        <button 
                                                            type="button" 
                                                            x-data="{ copied: false }" 
                                                            @click="navigator.clipboard.writeText('{{ $settlementInvoice['settlement_url'] }}'); copied = true; setTimeout(() => copied = false, 2000);"
                                                            class="flex-1 inline-flex items-center justify-center rounded-lg bg-green-600 px-5 py-3 text-sm font-semibold text-white shadow hover:bg-green-700 transition"
                                                        >
                                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!copied">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                            </svg>
                                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="copied" x-cloak>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                            <span x-show="!copied">Salin Link Pelunasan</span>
                                                            <span x-show="copied" x-cloak>Link Disalin!</span>
                                                        </button>
                                                        
                                                        <a 
                                                            href="{{ $settlementInvoice['settlement_url'] }}" 
                                                            target="_blank" 
                                                            rel="noopener noreferrer"
                                                            class="flex-1 inline-flex items-center justify-center rounded-lg bg-white border-2 border-green-600 px-5 py-3 text-sm font-semibold text-green-600 shadow hover:bg-green-50 transition"
                                                        >
                                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                            </svg>
                                                            Buka Link Pelunasan
                                                        </a>
                                                    </div>

                                                    <p class="mt-4 text-xs text-green-700">
                                                        Bagikan link ini kepada klien untuk melakukan pelunasan invoice. Link akan kadaluarsa setelah waktu yang ditentukan.
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <p class="mt-8 text-center text-sm text-gray-500">
                Setelah invoice dibuat, Anda juga dapat membagikan link publik kepada klien menggunakan token yang tersedia di dashboard customer service.
            </p>
        </div>
    </div>

</body>
</html>