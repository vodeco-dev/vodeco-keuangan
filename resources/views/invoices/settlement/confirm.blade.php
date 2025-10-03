@extends('layouts.public')

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h1 class="text-2xl font-semibold">Konfirmasi Pelunasan Invoice</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Pastikan Anda hanya melanjutkan proses ini apabila pembayaran sudah diterima.
            </p>
        </div>

        <div class="px-6 py-6 space-y-4">
            <div>
                <h2 class="text-lg font-semibold">Ringkasan Invoice</h2>
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
                        <dt class="text-gray-500 dark:text-gray-400">Jatuh Tempo</dt>
                        <dd class="font-medium">
                            {{ optional($invoice->due_date)->translatedFormat('d F Y') ?? 'Tidak ditentukan' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Total Tagihan</dt>
                        <dd class="font-medium">Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Status Saat Ini</dt>
                        <dd class="font-medium capitalize">{{ $invoice->status ?? 'belum bayar' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Berlaku Hingga</dt>
                        <dd class="font-medium">
                            @if ($invoice->settlement_token_expires_at)
                                {{ $invoice->settlement_token_expires_at->setTimezone(config('app.timezone'))->format('d F Y H:i') }}
                                WIB
                            @else
                                Tidak ditentukan
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            @if ($invoice->status === 'lunas')
                <div class="rounded-lg border border-green-300 bg-green-50 p-4 text-green-800">
                    Invoice ini sudah ditandai lunas sebelumnya. Tidak ada tindakan lanjutan yang diperlukan.
                </div>
            @else
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-800">
                    Dengan menekan tombol konfirmasi, Anda menyatakan bahwa pembayaran telah diterima dan invoice akan
                    ditandai lunas secara permanen.
                </div>

                <form method="POST" action="{{ route('invoices.settlement.store', ['token' => $token]) }}" class="space-y-4">
                    @csrf
                    <button type="submit"
                        class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-4 py-3 text-base font-semibold text-white shadow hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">
                        Konfirmasi Pelunasan
                    </button>
                </form>
            @endif
        </div>

        <div class="px-6 py-4 bg-gray-50 text-xs text-gray-500 dark:bg-gray-900 dark:text-gray-400">
            Jika Anda menerima tautan ini secara tidak sengaja, mohon hubungi tim Vodeco untuk memastikan keamanan akun Anda.
        </div>
    </div>
@endsection
