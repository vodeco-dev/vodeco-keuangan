<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Cek Konfirmasi Invoice') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
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
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Masukkan Nomor Invoice
                        </h3>
                        <form method="POST" action="{{ route('invoices.search-confirmation') }}">
                            @csrf
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1">
                                    <label for="invoice_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Nomor Invoice
                                    </label>
                                    <input 
                                        type="text" 
                                        name="invoice_number" 
                                        id="invoice_number" 
                                        value="{{ old('invoice_number', $searchedNumber ?? '') }}"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-lg"
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
                                        class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md shadow-sm transition-colors duration-200"
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
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                            <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Hasil Pencarian
                                </h3>
                                <!-- Link PDF dengan Status -->
                                <div class="flex items-center gap-3">
                                    <div class="text-right">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Status Invoice</p>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                            {{ $confirmationStatus['color'] === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                               ($confirmationStatus['color'] === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                               ($confirmationStatus['color'] === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                               ($confirmationStatus['color'] === 'orange' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 
                                               'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'))) }}">
                                            {{ $confirmationStatus['icon'] }} {{ $confirmationStatus['label'] }}
                                        </span>
                                    </div>
                                    <a 
                                        href="{{ route('invoices.pdf', $invoice) }}" 
                                        target="_blank"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md shadow-sm transition-colors duration-200"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        Unduh PDF
                                    </a>
                                </div>
                            </div>

                            <!-- Status Card -->
                            <div class="mb-6 rounded-lg border-2 {{ $confirmationStatus['color'] === 'green' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : ($confirmationStatus['color'] === 'yellow' ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' : ($confirmationStatus['color'] === 'red' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : ($confirmationStatus['color'] === 'orange' ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/20' : 'border-gray-500 bg-gray-50 dark:bg-gray-900/20'))) }} p-6">
                                <div class="flex items-start">
                                    <span class="text-4xl mr-4">{{ $confirmationStatus['icon'] }}</span>
                                    <div class="flex-1">
                                        <h4 class="text-xl font-bold {{ $confirmationStatus['color'] === 'green' ? 'text-green-800 dark:text-green-200' : ($confirmationStatus['color'] === 'yellow' ? 'text-yellow-800 dark:text-yellow-200' : ($confirmationStatus['color'] === 'red' ? 'text-red-800 dark:text-red-200' : ($confirmationStatus['color'] === 'orange' ? 'text-orange-800 dark:text-orange-200' : 'text-gray-800 dark:text-gray-200'))) }} mb-2">
                                            {{ $confirmationStatus['label'] }}
                                        </h4>
                                        <p class="{{ $confirmationStatus['color'] === 'green' ? 'text-green-700 dark:text-green-300' : ($confirmationStatus['color'] === 'yellow' ? 'text-yellow-700 dark:text-yellow-300' : ($confirmationStatus['color'] === 'red' ? 'text-red-700 dark:text-red-300' : ($confirmationStatus['color'] === 'orange' ? 'text-orange-700 dark:text-orange-300' : 'text-gray-700 dark:text-gray-300'))) }}">
                                            {{ $confirmationStatus['description'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Detail Invoice -->
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6 mb-6">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Detail Invoice</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Nomor Invoice</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $invoice->number }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Nama Klien</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $invoice->client_name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Tanggal Terbit</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $invoice->issue_date->format('d M Y') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Tanggal Jatuh Tempo</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $invoice->due_date->format('d M Y') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Total</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($invoice->total, 0, ',', '.') }}</p>
                                    </div>
                                    @if($invoice->down_payment)
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Down Payment</p>
                                            <p class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($invoice->down_payment, 0, ',', '.') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Informasi Pembayaran -->
                            @if($confirmationStatus['has_payment_proof'] || $confirmationStatus['payment_date'])
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 mb-6">
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Informasi Pembayaran
                                    </h4>
                                    <div class="space-y-3">
                                        @if($confirmationStatus['payment_proof_uploaded_at'])
                                            <div>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">Bukti Pembayaran Dikirim</p>
                                                <p class="font-semibold text-gray-900 dark:text-white">
                                                    {{ $confirmationStatus['payment_proof_uploaded_at']->format('d M Y, H:i') }} WIB
                                                </p>
                                            </div>
                                        @endif
                                        @if($confirmationStatus['payment_date'])
                                            <div>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">Tanggal Pembayaran Dikonfirmasi</p>
                                                <p class="font-semibold text-gray-900 dark:text-white">
                                                    {{ \Carbon\Carbon::parse($confirmationStatus['payment_date'])->format('d M Y') }}
                                                </p>
                                            </div>
                                        @endif
                                        @if($confirmationStatus['has_payment_proof'])
                                            <div class="pt-3">
                                                <a 
                                                    href="{{ $invoice->payment_proof_url }}" 
                                                    target="_blank"
                                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md transition-colors duration-200"
                                                >
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                    Lihat Bukti Pembayaran
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Item Invoice -->
                            @if($invoice->items->count() > 0)
                                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                                        <h4 class="font-semibold text-gray-900 dark:text-white">Item Invoice</h4>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-700/30">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Deskripsi</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jumlah</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Harga Satuan</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach($invoice->items as $item)
                                                    <tr>
                                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $item->description }}</td>
                                                        <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-white">{{ $item->quantity }}</td>
                                                        <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-white">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                                        <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900 dark:text-white">Rp {{ number_format($item->quantity * $item->unit_price, 0, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            <!-- Tombol Kembali -->
                            <div class="mt-8 flex justify-center">
                                <a 
                                    href="{{ route('invoices.check-confirmation') }}"
                                    class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-md shadow-sm transition-colors duration-200"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                    Cek Invoice Lain
                                </a>
                            </div>
                        </div>
                    @endisset

                    @empty($invoice)
                        <!-- Informasi Penggunaan -->
                        <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                            <h4 class="font-semibold text-blue-900 dark:text-blue-200 mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Cara Menggunakan
                            </h4>
                            <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-300">
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
        </div>
    </div>
</x-app-layout>
