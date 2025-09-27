@extends('layouts.app')

@section('content')
{{-- Header Halaman --}}
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-8">
    <h2 class="font-semibold text-2xl text-gray-800 dark:text-white leading-tight">
        {{ __($title) }}
    </h2>
</div>

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

    {{-- Kartu Ringkasan Saldo, Pemasukan, dan Pengeluaran --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
        {{-- Kartu Total Saldo --}}
        <div class="p-6 bg-white rounded-2xl shadow-lg">
            <p class="text-sm font-medium text-gray-500">Total Saldo</p>
            <p class="mt-1 text-3xl font-bold text-gray-900 ">
                Rp {{ number_format($summary['saldo'] ?? 0, 0, ',', '.') }}
            </p>
        </div>
        {{-- Kartu Pemasukan --}}
        <div class="p-6 bg-white rounded-2xl shadow-lg">
            <p class="text-sm font-medium text-gray-500">Pemasukan</p>
            <p class="mt-1 text-3xl font-bold text-green-600">
                Rp {{ number_format($summary['totalPemasukan'] ?? 0, 0, ',', '.') }}
            </p>
        </div>
        {{-- Kartu Pengeluaran --}}
        <div class="p-6 bg-white rounded-2xl shadow-lg">
            <p class="text-sm font-medium text-gray-500">Pengeluaran</p>
            <p class="mt-1 text-3xl font-bold text-red-600">
                Rp {{ number_format($summary['totalPengeluaran'] ?? 0, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Keadaan Keuangan & Transaksi Terbaru --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
        {{-- Keadaan Keuangan --}}
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 ">Keadaan Keuangan</h3>
                <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-2 mt-4 sm:mt-0">
                    <input type="month" name="month" class="border rounded-lg text-sm px-4 py-2" value="{{ $selected_month }}">
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">Filter</button>
                </form>
            </div>

            @php
                $monthName = \Carbon\Carbon::createFromFormat('Y-m', $selected_month)->translatedFormat('F Y');
            @endphp
            <p class="text-sm text-gray-500">Bulan: {{ $monthName }}</p>
            <p class="mt-2 text-lg font-semibold {{ $financial_overview['net'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $financial_overview['net'] >= 0 ? 'Surplus' : 'Defisit' }} Rp {{ number_format(abs($financial_overview['net']), 0, ',', '.') }}
            </p>
            <div class="mt-4 space-y-1">
                <p class="text-sm text-gray-600">Pemasukan: Rp {{ number_format($financial_overview['pemasukan'], 0, ',', '.') }}</p>
                <p class="text-sm text-gray-600">Pengeluaran: Rp {{ number_format($financial_overview['pengeluaran'], 0, ',', '.') }}</p>
            </div>
            <div class="mt-4">
                @php $change = $financial_overview['percent_change']; @endphp
                @if(!is_null($change))
                    @if($change > 0)
                        <p class="text-sm text-green-600">Naik {{ number_format($change, 2) }}% dari bulan sebelumnya</p>
                    @elseif($change < 0)
                        <p class="text-sm text-red-600">Turun {{ number_format(abs($change), 2) }}% dari bulan sebelumnya</p>
                    @else
                        <p class="text-sm text-gray-600">Tidak berubah dari bulan sebelumnya</p>
                    @endif
                @else
                    <p class="text-sm text-gray-600">Tidak ada data bulan sebelumnya</p>
                @endif
            </div>
        </div>

        {{-- Transaksi Terbaru --}}
        <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Transaksi Terbaru</h3>
            <div class="overflow-x-auto bg-white rounded-2xl shadow-lg">
                <table class="w-full text-left">
                    <thead class="border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase">Tanggal</th>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase">Kategori</th>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase">Deskripsi</th>
                            @if (!empty($show_user_column))
                                <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase">Pengguna</th>
                            @endif
                            <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($recent_transactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($transaction->date)->isoFormat('D MMMM YYYY') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900 ">{{ $transaction->category->name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $transaction->description ?: '-' }}</div>
                                    </td>
                                    @if (!empty($show_user_column))
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ $transaction->user?->name ?? '-' }}</div>
                                        </td>
                                    @endif
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                        @if ($transaction->category->type == 'pemasukan')
                                            <span class="text-green-600 font-semibold">+ Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-red-600 font-semibold">- Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ !empty($show_user_column) ? 5 : 4 }}" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                                        Belum ada transaksi untuk ditampilkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
