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

                        @if (empty($allowedTransactions))
                            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-800">
                                Passphrase aktif tidak memiliki tipe transaksi yang diizinkan. Hubungi administrator untuk memperbarui konfigurasi.
                            </div>
                        @else
                            <form action="{{ route('invoices.public.store') }}" method="POST" class="space-y-10" id="invoice-form"
                                enctype="multipart/form-data"
                                x-data="{
                                    activeTab: '{{ $defaultTransaction }}',
                                    proofConfirmed: false,
                                    init() {
                                        window.addEventListener('invoice-transaction-tab-changed', (event) => {
                                            if (!event.detail || !event.detail.tab) {
                                                return;
                                            }

                                            this.activeTab = event.detail.tab;
                                        });

                                        this.$watch('proofConfirmed', (value) => {
                                            if (!value && this.$refs.paymentProof) {
                                                this.$refs.paymentProof.value = '';
                                            }
                                        });
                                    },
                                }">
                                @csrf
                                <input type="hidden" name="passphrase_token" value="{{ $passphraseToken }}">

                                <div class="rounded-lg border border-blue-100 bg-blue-50 p-5">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <h2 class="text-lg font-semibold text-blue-900">Konfirmasi Bukti Pembayaran</h2>
                                            <p class="mt-1 text-sm text-blue-800">Unggah bukti pembayaran dalam format PNG atau JPG sebelum mengirim formulir. Bukti akan diverifikasi kembali oleh tim akuntansi.</p>
                                        </div>
                                        <button type="button"
                                            class="inline-flex items-center justify-center rounded-lg border border-blue-300 bg-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50"
                                            @click="proofConfirmed = !proofConfirmed">
                                            <span x-text="proofConfirmed ? 'Batalkan Konfirmasi' : 'Konfirmasi & Unggah Bukti'"></span>
                                        </button>
                                    </div>
                                    <p class="mt-3 text-xs text-blue-700" x-show="!proofConfirmed" x-cloak>
                                        Tekan tombol konfirmasi untuk membuka kolom unggah bukti.
                                    </p>
                                </div>

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
                                    data-reference-url-template="{{ route('invoices.public.reference', ['number' => '__NUMBER__']) }}"
                                />

                                <div class="space-y-2">
                                    <label for="payment_proof" class="block text-sm font-medium text-gray-700">Bukti Pembayaran (PNG atau JPG)</label>
                                    <input type="file" name="payment_proof" id="payment_proof" x-ref="paymentProof"
                                        accept="image/png,image/jpeg"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        :required="proofConfirmed"
                                        :disabled="!proofConfirmed">
                                    <p class="text-xs text-gray-500">Pastikan bukti terlihat jelas. File maksimal 5MB.</p>
                                    @error('payment_proof')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-green-600 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="!proofConfirmed">
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

</body>
</html>
