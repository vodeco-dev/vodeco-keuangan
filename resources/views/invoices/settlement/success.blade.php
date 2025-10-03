@extends('layouts.public')

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden">
        <div class="px-6 py-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">
                        @if ($alreadySettled)
                            Invoice Sudah Lunas
                        @else
                            Pelunasan Berhasil Dikonfirmasi
                        @endif
                    </h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        Terima kasih telah melakukan konfirmasi pembayaran.
                    </p>
                </div>
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                <h2 class="text-lg font-semibold">Ringkasan</h2>
                <dl class="mt-3 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Nomor Invoice</dt>
                        <dd class="font-medium">{{ $invoice->number }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Nama Klien</dt>
                        <dd class="font-medium">{{ $invoice->client_name ?? 'Tidak tersedia' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Tanggal Pembayaran</dt>
                        <dd class="font-medium">
                            @php
                                $settledAt = $invoice->payment_date
                                    ? \Illuminate\Support\Carbon::parse($invoice->payment_date)->setTimezone(config('app.timezone'))
                                    : null;
                            @endphp
                            {{ $settledAt ? $settledAt->format('d F Y H:i') . ' WIB' : '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Total Pembayaran</dt>
                        <dd class="font-medium">Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-800 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-100">
                Tautan konfirmasi ini tidak lagi aktif. Silakan hubungi tim keuangan Vodeco jika membutuhkan bukti tambahan.
            </div>
        </div>
    </div>
@endsection
