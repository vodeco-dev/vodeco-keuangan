<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Cek Konfirmasi Invoice - Vodeco</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-900" style="background-image: url('{{ asset('background-vodeco.jpg') }}'); background-size: cover; background-position: center; background-attachment: fixed;">
    <div class="min-h-screen py-10">
        <div class="max-w-5xl mx-auto px-4">
            <div class="mb-10 text-center">
                <img src="{{ asset('logo-vodeco-dark-mode.png') }}" alt="Logo Vodeco" class="mx-auto h-16">
                <h1 class="mt-4 text-3xl font-semibold text-white">Cek Konfirmasi Invoice</h1>
                <p class="mt-2 text-indigo-100">Masukkan nomor invoice untuk melihat status konfirmasi pembayaran.</p>
            </div>

            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-8 md:px-10 space-y-8">
                    @if ($passphraseSession)
                        <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4 text-indigo-800">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm uppercase tracking-wide">Akses Terverifikasi</span>
                                    <span class="text-base font-semibold">{{ $passphraseSession['display_label'] ?? $passphraseSession['access_label'] ?? 'Portal Invoice' }}</span>
                                </div>
                                <div class="flex gap-2 flex-shrink-0">
                                    <a href="{{ route('invoices.public.create') }}" class="inline-flex items-center rounded-lg border border-indigo-200 bg-white/80 px-3 py-1.5 text-sm font-medium text-indigo-700 shadow-sm hover:bg-white">
                                        Kembali ke Portal
                                    </a>
                                    <form method="POST" action="{{ route('invoices.public.passphrase.logout') }}" class="flex-shrink-0">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center rounded-lg border border-indigo-200 bg-white/80 px-3 py-1.5 text-sm font-medium text-indigo-700 shadow-sm hover:bg-white">Keluar dari Sesi</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Pesan Error -->
                    @if (session('error'))
                        <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <div class="flex items-center">
                                <span class="text-xl mr-3">❌</span>
                                <span>{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Form Pencarian Invoice -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Masukkan Nomor Invoice
                        </h3>
                        <form method="POST" action="{{ route('invoices.public.search-confirmation') }}">
                            @csrf
                            @if($passphraseToken)
                                <input type="hidden" name="passphrase_token" value="{{ $passphraseToken }}">
                            @endif
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1">
                                    <label for="invoice_number" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nomor Invoice
                                    </label>
                                    <input 
                                        type="text" 
                                        name="invoice_number" 
                                        id="invoice_number" 
                                        value="{{ old('invoice_number', $searchedNumber ?? '') }}"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-lg"
                                        placeholder="Contoh: INV-2024-001"
                                        required
                                        autofocus
                                    >
                                    @error('invoice_number')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:pt-7">
                                    <button 
                                        type="submit" 
                                        class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-sm transition-colors duration-200"
                                    >
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                        Cek Status
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    @isset($invoice)
                        <!-- Hasil Pencarian -->
                        <div class="border-t border-gray-200 pt-8">
                            <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Hasil Pencarian
                                </h3>
                                <!-- Link PDF dengan Status -->
                                <div class="flex items-center gap-3">
                                    <div class="text-right">
                                        <p class="text-xs text-gray-600 mb-1">Status Invoice</p>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                            {{ $confirmationStatus['color'] === 'green' ? 'bg-green-100 text-green-800' : 
                                               ($confirmationStatus['color'] === 'yellow' ? 'bg-yellow-100 text-yellow-800' : 
                                               ($confirmationStatus['color'] === 'red' ? 'bg-red-100 text-red-800' : 
                                               ($confirmationStatus['color'] === 'orange' ? 'bg-orange-100 text-orange-800' : 
                                               'bg-gray-100 text-gray-800'))) }}">
                                            {{ $confirmationStatus['icon'] }} {{ $confirmationStatus['label'] }}
                                        </span>
                                    </div>
                                    <a 
                                        href="{{ route('invoices.public.pdf-hosted', ['token' => $invoice->public_token]) }}" 
                                        target="_blank"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-200"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        Unduh PDF
                                    </a>
                                </div>
                            </div>

                            <!-- Status Card -->
                            <div class="mb-6 rounded-lg border-2 {{ $confirmationStatus['color'] === 'green' ? 'border-green-500 bg-green-50' : ($confirmationStatus['color'] === 'yellow' ? 'border-yellow-500 bg-yellow-50' : ($confirmationStatus['color'] === 'red' ? 'border-red-500 bg-red-50' : ($confirmationStatus['color'] === 'orange' ? 'border-orange-500 bg-orange-50' : 'border-gray-500 bg-gray-50'))) }} p-6">
                                <div class="flex items-start">
                                    <span class="text-4xl mr-4">{{ $confirmationStatus['icon'] }}</span>
                                    <div class="flex-1">
                                        <h4 class="text-xl font-bold {{ $confirmationStatus['color'] === 'green' ? 'text-green-800' : ($confirmationStatus['color'] === 'yellow' ? 'text-yellow-800' : ($confirmationStatus['color'] === 'red' ? 'text-red-800' : ($confirmationStatus['color'] === 'orange' ? 'text-orange-800' : 'text-gray-800'))) }} mb-2">
                                            {{ $confirmationStatus['label'] }}
                                        </h4>
                                        <p class="{{ $confirmationStatus['color'] === 'green' ? 'text-green-700' : ($confirmationStatus['color'] === 'yellow' ? 'text-yellow-700' : ($confirmationStatus['color'] === 'red' ? 'text-red-700' : ($confirmationStatus['color'] === 'orange' ? 'text-orange-700' : 'text-gray-700'))) }}">
                                            {{ $confirmationStatus['description'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Detail Invoice -->
                            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                                <h4 class="font-semibold text-gray-900 mb-4">Detail Invoice</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Nomor Invoice</p>
                                        <p class="font-semibold text-gray-900">{{ $invoice->number }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Nama Klien</p>
                                        <p class="font-semibold text-gray-900">{{ $invoice->client_name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Tanggal Terbit</p>
                                        <p class="font-semibold text-gray-900">{{ $invoice->issue_date->format('d M Y') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Tanggal Jatuh Tempo</p>
                                        <p class="font-semibold text-gray-900">{{ $invoice->due_date->format('d M Y') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Total</p>
                                        <p class="font-semibold text-gray-900">Rp {{ number_format($invoice->total, 0, ',', '.') }}</p>
                                    </div>
                                    @if($invoice->down_payment)
                                        <div>
                                            <p class="text-sm text-gray-600">Down Payment</p>
                                            <p class="font-semibold text-gray-900">Rp {{ number_format($invoice->down_payment, 0, ',', '.') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Informasi Pembayaran -->
                            @if($confirmationStatus['has_payment_proof'] || $confirmationStatus['payment_date'])
                                <div class="bg-blue-50 rounded-lg p-6 mb-6">
                                    <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Informasi Pembayaran
                                    </h4>
                                    <div class="space-y-3">
                                        @if($confirmationStatus['payment_proof_uploaded_at'])
                                            <div>
                                                <p class="text-sm text-gray-600">Bukti Pembayaran Dikirim</p>
                                                <p class="font-semibold text-gray-900">
                                                    {{ $confirmationStatus['payment_proof_uploaded_at']->format('d M Y, H:i') }} WIB
                                                </p>
                                            </div>
                                        @endif
                                        @if($confirmationStatus['payment_date'])
                                            <div>
                                                <p class="text-sm text-gray-600">Tanggal Pembayaran Dikonfirmasi</p>
                                                <p class="font-semibold text-gray-900">
                                                    {{ \Carbon\Carbon::parse($confirmationStatus['payment_date'])->format('d M Y') }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Item Invoice -->
                            @if($invoice->items->count() > 0)
                                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                        <h4 class="font-semibold text-gray-900">Item Invoice</h4>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Satuan</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach($invoice->items as $item)
                                                    <tr>
                                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $item->description }}</td>
                                                        <td class="px-6 py-4 text-sm text-right text-gray-900">{{ $item->quantity }}</td>
                                                        <td class="px-6 py-4 text-sm text-right text-gray-900">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                                        <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900">Rp {{ number_format($item->quantity * $item->unit_price, 0, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            <!-- Tombol Kembali -->
                            <div class="mt-8 flex justify-center gap-4">
                                <a 
                                    href="{{ route('invoices.public.check-confirmation') }}"
                                    class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-sm transition-colors duration-200"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                    Cek Invoice Lain
                                </a>
                                @if($passphraseSession)
                                    <a 
                                        href="{{ route('invoices.public.create') }}"
                                        class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition-colors duration-200"
                                    >
                                        Kembali ke Portal
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endisset

                    @empty($invoice)
                        <!-- Informasi Penggunaan -->
                        <div class="mt-8 bg-blue-50 rounded-lg p-6">
                            <h4 class="font-semibold text-blue-900 mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Cara Menggunakan
                            </h4>
                            <ul class="space-y-2 text-sm text-blue-800">
                                <li class="flex items-start">
                                    <span class="mr-2">1.</span>
                                    <span>Masukkan nomor invoice yang ingin Anda cek pada form di atas</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="mr-2">2.</span>
                                    <span>Klik tombol "Cek Status" untuk melihat informasi konfirmasi pembayaran</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="mr-2">3.</span>
                                    <span>Sistem akan menampilkan status terkini invoice dan detail pembayaran jika ada</span>
                                </li>
                            </ul>
                        </div>
                    @endempty
                </div>
            </div>

            @if($passphraseSession)
                <p class="mt-8 text-center text-sm text-indigo-100/80">
                    Anda juga dapat membuat invoice baru atau mengunggah bukti pembayaran melalui portal.
                </p>
            @endif
        </div>
    </div>
</body>
</html>
