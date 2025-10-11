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
                    @if (session('status'))
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
                            $activePortalTab = old('portal_mode') ?: session('active_portal_tab', 'create_invoice');

                            if (! in_array($activePortalTab, $allowedPortalTabs, true)) {
                                $activePortalTab = 'create_invoice';
                            }

                            $passThroughPackagesByType = $passThroughPackagesByType ?? [];
                            $passThroughPackagesById = $passThroughPackagesById ?? [];
                            $defaultPassThroughEnabled = filter_var(old('pass_through_enabled'), FILTER_VALIDATE_BOOLEAN);
                            $defaultPassThroughCustomerType = old('pass_through_customer_type', \App\Support\PassThroughPackage::CUSTOMER_TYPE_NEW);
                            $defaultPassThroughPackageId = old('pass_through_package_id');
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
                                <div class="flex items-center gap-2 rounded-xl bg-gray-100 p-1">
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
                                </div>
                                <p class="text-xs text-gray-500 md:text-sm" x-show="activePortalTab === 'confirm_payment'" x-cloak>
                                    Unggah bukti pembayaran dengan menyertakan nomor invoice yang telah diterbitkan.
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
                                            x-data="publicInvoiceForm({
                                                defaultTransaction: @json($defaultTransaction),
                                                packagesByType: @json($passThroughPackagesByType),
                                                packagesById: @json($passThroughPackagesById),
                                                defaultPassThroughEnabled: @json($defaultPassThroughEnabled),
                                                defaultCustomerType: @json($defaultPassThroughCustomerType),
                                                defaultPackage: @json($defaultPassThroughPackageId),
                                            })">
                                            @csrf
                                            <input type="hidden" name="passphrase_token" value="{{ $passphraseToken }}">
                                            <input type="hidden" name="portal_mode" value="create_invoice">
                                            <input type="hidden" name="pass_through_enabled" :value="passThroughEnabled ? 1 : 0">

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
                                                        <label for="client_address" class="block text-sm font-medium text-gray-700">Alamat Klien</label>
                                                        <textarea name="client_address" id="client_address" rows="6" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :required="activeTab !== 'settlement'" :disabled="activeTab === 'settlement'">{{ old('client_address') }}</textarea>
                                                        @error('client_address')
                                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                        @enderror
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

                                            <div
                                                class="space-y-6 rounded-2xl border border-gray-200 bg-gray-50 p-6"
                                                x-show="activeTab === 'pass_through'"
                                                x-cloak
                                            >
                                                <div class="space-y-2">
                                                    <h3 class="text-lg font-semibold text-gray-900">Invoice Pass Through</h3>
                                                    <p class="text-sm text-gray-600">Gunakan paket pass through yang telah dikonfigurasi tim keuangan untuk mengisi rincian secara otomatis.</p>
                                                </div>

                                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                                    <div class="space-y-1">
                                                        <p class="text-sm font-medium text-gray-800">Tab Pass Through Aktif</p>
                                                        <p class="text-xs text-gray-500">Paket pass through akan diterapkan otomatis ketika membuat invoice dari tab ini.</p>
                                                    </div>
                                                    <span
                                                        class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold"
                                                        :class="hasAvailablePackages() ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'"
                                                    >
                                                        <span x-text="hasAvailablePackages() ? 'Paket Tersedia' : 'Paket Tidak Tersedia'"></span>
                                                    </span>
                                                </div>

                                                <div x-show="!hasAvailablePackages()" x-cloak class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700">
                                                    Belum ada paket pass through yang tersedia. Silakan hubungi tim keuangan untuk mengaktifkan paket terlebih dahulu.
                                                </div>

                                                <div x-show="passThroughEnabled && hasAvailablePackages()" x-cloak class="space-y-6">
                                                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700">Jenis Pelanggan</label>
                                                            <select name="pass_through_customer_type" x-model="selectedCustomerType" :disabled="!passThroughEnabled" class="mt-1 block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                                <option value="{{ \App\Support\PassThroughPackage::CUSTOMER_TYPE_NEW }}">Pelanggan Baru</option>
                                                                <option value="{{ \App\Support\PassThroughPackage::CUSTOMER_TYPE_EXISTING }}">Pelanggan Lama</option>
                                                            </select>
                                                            @error('pass_through_customer_type')
                                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700">Paket Pass Through</label>
                                                            <select name="pass_through_package_id" x-model="selectedPackageId" :disabled="!passThroughEnabled || packageOptions().length === 0" class="mt-1 block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                                <option value="">Pilih paket</option>
                                                                <template x-for="option in packageOptions()" :key="option.id">
                                                                    <option :value="option.id" x-text="option.name"></option>
                                                                </template>
                                                            </select>
                                                            @error('pass_through_package_id')
                                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                            @enderror
                                                        </div>
                                                    </div>

                                                    <div x-show="packageOptions().length === 0" x-cloak class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700">
                                                        Tidak ada paket yang sesuai dengan jenis pelanggan yang dipilih.
                                                    </div>

                                                    <div x-show="selectedPackageData()" x-cloak class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                                                        <h4 class="text-base font-semibold text-gray-900">Ringkasan Paket</h4>
                                                        <dl class="mt-4 grid grid-cols-1 gap-4 text-sm text-gray-700 sm:grid-cols-2">
                                                            <div>
                                                                <dt class="font-medium text-gray-600">Harga Paket</dt>
                                                                <dd class="mt-1 text-base font-semibold text-gray-900" x-text="formatCurrency(selectedPackageData()?.package_price || 0)"></dd>
                                                            </div>
                                                            <div>
                                                                <dt class="font-medium text-gray-600">Saldo Harian Terpotong</dt>
                                                                <dd class="mt-1" x-text="formatCurrency(selectedPackageData()?.daily_deduction || 0)"></dd>
                                                            </div>
                                                            <div x-show="selectedCustomerType === '{{ \App\Support\PassThroughPackage::CUSTOMER_TYPE_NEW }}'">
                                                                <dt class="font-medium text-gray-600">Biaya Pembuatan Akun</dt>
                                                                <dd class="mt-1" x-text="formatCurrency(selectedPackageData()?.account_creation_fee || 0)"></dd>
                                                            </div>
                                                            <div>
                                                                <dt class="font-medium text-gray-600">Biaya Maintenance</dt>
                                                                <dd class="mt-1" x-text="formatCurrency(selectedPackageData()?.maintenance_fee || 0)"></dd>
                                                            </div>
                                                            <div>
                                                                <dt class="font-medium text-gray-600">Biaya Perpanjangan</dt>
                                                                <dd class="mt-1" x-text="formatCurrency(selectedPackageData()?.renewal_fee || 0)"></dd>
                                                            </div>
                                                            <div class="sm:col-span-2">
                                                                <dt class="font-medium text-gray-600">Dana Pass Through</dt>
                                                                <dd class="mt-1 text-lg font-semibold text-gray-900" x-text="formatCurrency(passThroughAmount())"></dd>
                                                            </div>
                                                        </dl>
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

    <script>
        function publicInvoiceForm(config) {
            return {
                activeTab: config.defaultTransaction || 'down_payment',
                packagesByType: config.packagesByType || {},
                packagesById: config.packagesById || {},
                passThroughEnabled: Boolean(config.defaultPassThroughEnabled),
                selectedCustomerType: config.defaultCustomerType || 'new',
                selectedPackageId: config.defaultPackage || '',
                init() {
                    window.addEventListener('invoice-transaction-tab-changed', (event) => {
                        if (!event.detail || !event.detail.tab) {
                            return;
                        }

                        this.activeTab = event.detail.tab;
                        this.syncPassThroughState();
                        this.$nextTick(() => this.syncTransactionComponents());
                    });

                    if (!this.hasAvailablePackages()) {
                        this.passThroughEnabled = false;
                    }

                    this.ensureDefaultPackage();
                    this.syncPassThroughState();
                    this.$nextTick(() => this.syncTransactionComponents());

                    this.$watch('passThroughEnabled', () => {
                        if (this.passThroughEnabled) {
                            this.ensureDefaultPackage();
                        }

                        this.$nextTick(() => this.syncTransactionComponents());
                    });

                    this.$watch('selectedCustomerType', () => {
                        this.ensureDefaultPackage();
                        this.syncPassThroughState();
                        this.$nextTick(() => this.syncTransactionComponents());
                    });
                },
                hasAvailablePackages() {
                    return Object.values(this.packagesByType).some((options) => Array.isArray(options) && options.length > 0);
                },
                syncPassThroughState() {
                    const shouldEnable = this.activeTab === 'pass_through' && this.hasAvailablePackages();

                    if (this.passThroughEnabled !== shouldEnable) {
                        this.passThroughEnabled = shouldEnable;
                    }
                },
                packageOptions() {
                    const options = this.packagesByType[this.selectedCustomerType] || [];

                    return Array.isArray(options) ? options : [];
                },
                ensureDefaultPackage() {
                    const options = this.packageOptions();

                    if (!options.length) {
                        this.selectedPackageId = '';
                        return;
                    }

                    const hasSelected = options.some((option) => option.id === this.selectedPackageId);

                    if (!hasSelected) {
                        this.selectedPackageId = options[0]?.id || '';
                    }
                },
                selectedPackageData() {
                    if (!this.selectedPackageId) {
                        return null;
                    }

                    return this.packagesById[this.selectedPackageId] || null;
                },
                passThroughAmount() {
                    const data = this.selectedPackageData();

                    if (!data) {
                        return 0;
                    }

                    let deductions = Number(data.maintenance_fee || 0) + Number(data.renewal_fee || 0);

                    if (this.selectedCustomerType === 'new') {
                        deductions += Number(data.account_creation_fee || 0);
                    }

                    const total = Number(data.package_price || 0) - deductions;

                    return total > 0 ? total : 0;
                },
                formatCurrency(value) {
                    const number = Number(value || 0);

                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0,
                    }).format(number);
                },
                syncTransactionComponents() {
                    const tabs = this.$refs.transactionTabs;

                    if (!tabs) {
                        return;
                    }

                    const itemsWrapper = tabs.querySelector('[data-items-wrapper]');

                    if (itemsWrapper) {
                        const shouldHideItems = this.passThroughEnabled || this.activeTab === 'settlement' || this.activeTab === 'pass_through';
                        itemsWrapper.style.display = shouldHideItems ? 'none' : '';

                        itemsWrapper.querySelectorAll('input, select, textarea').forEach((element) => {
                            if (this.passThroughEnabled || this.activeTab === 'pass_through') {
                                if (!element.dataset.passThroughDisabled) {
                                    element.dataset.passThroughDisabled = '1';
                                }

                                element.disabled = true;
                                element.required = false;
                            } else if (element.dataset.passThroughDisabled === '1' && this.activeTab !== 'settlement') {
                                element.disabled = false;
                                delete element.dataset.passThroughDisabled;
                            }
                        });
                    }

                    const addItemButton = tabs.querySelector('[data-add-item]');

                    if (addItemButton) {
                        if (this.passThroughEnabled || this.activeTab === 'pass_through') {
                            addItemButton.dataset.passThroughDisabled = '1';
                            addItemButton.disabled = true;
                            addItemButton.classList.add('hidden');
                        } else if (addItemButton.dataset.passThroughDisabled === '1' && this.activeTab !== 'settlement') {
                            addItemButton.disabled = false;
                            addItemButton.classList.remove('hidden');
                            delete addItemButton.dataset.passThroughDisabled;
                        } else if (this.activeTab === 'settlement') {
                            addItemButton.classList.add('hidden');
                        } else {
                            addItemButton.classList.remove('hidden');
                        }
                    }

                    const totalWrapper = tabs.querySelector('[data-total-wrapper]');

                    if (totalWrapper) {
                        const shouldHideTotal = this.passThroughEnabled || this.activeTab === 'settlement' || this.activeTab === 'pass_through';
                        totalWrapper.style.display = shouldHideTotal ? 'none' : '';
                    }

                    const downPaymentSection = tabs.querySelector('[data-down-payment-visible]');

                    if (downPaymentSection) {
                        if (this.passThroughEnabled) {
                            downPaymentSection.style.display = 'none';

                            downPaymentSection.querySelectorAll('input, select, textarea').forEach((element) => {
                                if (!element.dataset.passThroughDisabled) {
                                    element.dataset.passThroughDisabled = '1';
                                }

                                element.disabled = true;
                                element.required = false;
                            });
                        } else {
                            downPaymentSection.style.display = this.activeTab === 'down_payment' ? '' : 'none';

                            downPaymentSection.querySelectorAll('input, select, textarea').forEach((element) => {
                                if (element.dataset.passThroughDisabled === '1') {
                                    element.disabled = false;
                                    delete element.dataset.passThroughDisabled;
                                }
                            });
                        }
                    }
                },
            };
        }
    </script>
</body>
</html>
