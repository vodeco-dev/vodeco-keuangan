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
                            $allowedTransactions = array_values(array_intersect(['down_payment', 'full_payment', 'settlement'], $allowedTransactionTypes));
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
                                            x-data="{
                                                activeTab: '{{ $defaultTransaction }}',
                                                init() {
                                                    window.addEventListener('invoice-transaction-tab-changed', (event) => {
                                                        if (!event.detail || !event.detail.tab) {
                                                            return;
                                                        }

                                                        this.activeTab = event.detail.tab;
                                                    });
                                                },
                                            }">
                                            @csrf
                                            <input type="hidden" name="passphrase_token" value="{{ $passphraseToken }}">
                                            <input type="hidden" name="portal_mode" value="create_invoice">

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
                                            />

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

                                    @php
                                        $paymentReferenceUrlTemplate = route('invoices.public.payment-reference', ['number' => '__NUMBER__'], false);
                                    @endphp

                                    <form
                                        action="{{ route('invoices.public.payment-confirm') }}"
                                        method="POST"
                                        class="space-y-6"
                                        enctype="multipart/form-data"
                                        x-data="paymentConfirmationForm({
                                            referenceUrl: @js($paymentReferenceUrlTemplate),
                                            initialNumber: @json($initialInvoiceNumber),
                                            initialInvoice: @json($confirmedInvoiceSummary)
                                        })"
                                        x-init="init()"
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
                                                <div class="flex gap-3">
                                                    <input
                                                        type="text"
                                                        name="invoice_number"
                                                        id="invoice_number"
                                                        x-model="invoiceNumber"
                                                        @blur="lookupInvoice()"
                                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        required
                                                    >
                                                    <button
                                                        type="button"
                                                        class="mt-1 inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-600 shadow-sm hover:bg-indigo-50"
                                                        @click.prevent="lookupInvoice(true)"
                                                    >
                                                        Cek Invoice
                                                    </button>
                                                </div>
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

                                            <div x-show="loading" x-cloak class="rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-sm text-indigo-700">
                                                Memeriksa data invoice...
                                            </div>

                                            <div x-show="invoice || error" x-cloak class="space-y-4">
                                                <div x-show="invoice" x-cloak class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                                                    <h3 class="text-base font-semibold text-green-900">Ringkasan Invoice</h3>
                                                    <dl class="mt-3 space-y-1">
                                                        <div class="flex items-center justify-between">
                                                            <dt class="font-medium text-green-900">Nomor</dt>
                                                            <dd x-text="invoice?.number ?? '-'" class="text-green-900"></dd>
                                                        </div>
                                                        <div class="flex items-center justify-between">
                                                            <dt class="font-medium text-green-900">Customer Service</dt>
                                                            <dd x-text="invoice?.customer_service_name ?? '-'" class="text-green-900"></dd>
                                                        </div>
                                                        <div class="flex items-center justify-between">
                                                            <dt class="font-medium text-green-900">Klien</dt>
                                                            <dd x-text="invoice?.client_name ?? '-'" class="text-green-900"></dd>
                                                        </div>
                                                        <div class="flex items-center justify-between">
                                                            <dt class="font-medium text-green-900">Total Tagihan</dt>
                                                            <dd x-text="formatCurrency(invoice?.total)" class="text-green-900"></dd>
                                                        </div>
                                                        <div class="flex items-center justify-between">
                                                            <dt class="font-medium text-green-900">Pembayaran Masuk</dt>
                                                            <dd x-text="formatCurrency(invoice?.down_payment)" class="text-green-900"></dd>
                                                        </div>
                                                        <div class="flex items-center justify-between">
                                                            <dt class="font-medium text-green-900">Sisa Tagihan</dt>
                                                            <dd x-text="formatCurrency(invoice?.remaining_balance)" class="text-green-900"></dd>
                                                        </div>
                                                        <div class="flex items-center justify-between">
                                                            <dt class="font-medium text-green-900">Status</dt>
                                                            <dd x-text="formatStatus(invoice?.status)" class="text-green-900"></dd>
                                                        </div>
                                                    </dl>
                                                </div>
                                                <div x-show="!invoice && error" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                                    <p x-text="error"></p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex justify-end">
                                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                Kirim Bukti Pembayaran
                                            </button>
                                        </div>
                                    </form>
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
        function paymentConfirmationForm({ referenceUrl, initialNumber = '', initialInvoice = null }) {
            return {
                referenceUrl,
                invoiceNumber: initialNumber,
                invoice: initialInvoice,
                error: null,
                loading: false,
                lastFetchedNumber: initialInvoice?.number ?? null,
                init() {
                    if (this.invoice) {
                        this.lastFetchedNumber = this.invoice?.number ?? null;
                    } else if (this.invoiceNumber) {
                        this.lookupInvoice();
                    }
                },
                async lookupInvoice(force = false) {
                    const number = (this.invoiceNumber || '').trim();

                    if (!number) {
                        this.invoice = null;
                        this.error = null;
                        this.lastFetchedNumber = null;

                        return;
                    }

                    if (!force && this.lastFetchedNumber && this.lastFetchedNumber === number) {
                        return;
                    }

                    this.loading = true;
                    this.error = null;

                    try {
                        const response = await fetch(this.referenceUrl.replace('__NUMBER__', encodeURIComponent(number)), {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) {
                            let message = 'Invoice tidak ditemukan atau tidak dapat digunakan.';

                            try {
                                const data = await response.json();

                                if (data?.message) {
                                    message = data.message;
                                }
                            } catch (error) {
                                // Ignore JSON parsing error
                            }

                            throw new Error(message);
                        }

                        const data = await response.json();

                        this.invoice = data;
                        this.lastFetchedNumber = number;
                        this.error = null;
                    } catch (error) {
                        this.invoice = null;
                        this.lastFetchedNumber = null;
                        this.error = error?.message ?? 'Gagal memeriksa invoice.';
                    } finally {
                        this.loading = false;
                    }
                },
                formatCurrency(value) {
                    if (value === null || value === undefined || value === '') {
                        return '-';
                    }

                    const numberValue = Number(value);

                    if (!Number.isFinite(numberValue)) {
                        return value;
                    }

                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0,
                    }).format(numberValue);
                },
                formatStatus(status) {
                    switch (status) {
                        case 'lunas':
                            return 'Lunas';
                        case 'belum lunas':
                            return 'Belum Lunas';
                        default:
                            return 'Belum Bayar';
                    }
                },
            };
        }
    </script>
</body>
</html>
