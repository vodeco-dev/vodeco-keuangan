@extends('layouts.app')

@section('content')
{{-- Header Halaman --}}
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-8">
    <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
        {{ __($title) }}
    </h2>
</div>

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

    {{-- Kartu Ringkasan Saldo, Pemasukan, dan Pengeluaran --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
        {{-- Kartu Total Saldo --}}
        <div class="p-6 bg-white rounded-2xl shadow-lg">
            <p class="text-sm font-medium text-gray-500">Total Saldo</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">
                Rp {{ number_format($saldo, 0, ',', '.') }}
            </p>
        </div>
        {{-- Kartu Pemasukan --}}
        <div class="p-6 bg-white rounded-2xl shadow-lg">
            <p class="text-sm font-medium text-gray-500">Pemasukan</p>
            <p class="mt-1 text-3xl font-bold text-green-600">
                Rp {{ number_format($pemasukan, 0, ',', '.') }}
            </p>
        </div>
        {{-- Kartu Pengeluaran --}}
        <div class="p-6 bg-white rounded-2xl shadow-lg">
            <p class="text-sm font-medium text-gray-500">Pengeluaran</p>
            <p class="mt-1 text-3xl font-bold text-red-600">
                Rp {{ number_format($pengeluaran, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Grafik Tren Bulanan --}}
    <div class="mt-8 bg-white rounded-2xl shadow-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900">Tren Pemasukan & Pengeluaran</h3>

            {{-- Form Filter --}}
            <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-2 mt-4 sm:mt-0">
                <input type="month" name="month" class="border rounded-lg text-sm px-4 py-2" value="{{ request('month') }}">
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    Filter
                </button>
                <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                    Reset
                </a>
            </form>
        </div>

        {{-- Visualisasi Chart (Data Dinamis) --}}
        <div class="relative h-96" style="border-left: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;">
            <div class="absolute bottom-0 left-0 w-full h-full flex items-end justify-around px-4 pb-8">
                @forelse($monthly_trends as $trend)
                @php
                // (FIX) Pindahkan kalkulasi ke sini agar editor tidak bingung
                $pemasukanHeight = $max_trend_value > 0 ? ($trend->pemasukan / $max_trend_value) * 100 : 0;
                $pengeluaranHeight = $max_trend_value > 0 ? ($trend->pengeluaran / $max_trend_value) * 100 : 0;
                @endphp
                <div class="flex flex-col items-center flex-1 h-full pt-2">
                    <div class="w-full h-full flex flex-col justify-end">
                        <div class="flex items-end justify-center w-full gap-2">
                            {{-- Bar Pemasukan --}}
                            <div class="w-1/2 bg-green-400 rounded-t-md"
                                title="Pemasukan: {{ number_format($trend->pemasukan) }}"
                                style="height: {{ $pemasukanHeight }}%;">
                            </div>
                            {{-- Bar Pengeluaran --}}
                            <div class="w-1/2 bg-red-400 rounded-t-md"
                                title="Pengeluaran: {{ number_format($trend->pengeluaran) }}"
                                style="height: {{ $pengeluaranHeight }}%;">
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 font-semibold">
                        {{ \Carbon\Carbon::create()->month((int)$trend->month)->format('M') }}
                    </p>
                </div>
                @empty
                <div class="w-full h-full flex items-center justify-center">
                    <p class="text-gray-500">Tidak ada data tren untuk ditampilkan.</p>
                </div>
                @endforelse
            </div>
        </div>
        <div class="flex justify-center mt-4 gap-6">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-green-400"></div><span class="text-sm text-gray-500">Pemasukan</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-red-400"></div><span class="text-sm text-gray-500">Pengeluaran</span>
            </div>
        </div>
    </div>

    {{-- Tabel Transaksi Terbaru --}}
    <div class="mt-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Transaksi Terbaru</h3>
        <div class="overflow-x-auto bg-white rounded-2xl shadow-lg">
            <table class="w-full text-left">
                <thead class="border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase">Kategori</th>
                        <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase">Deskripsi</th>
                        <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($recent_transactions as $transaction)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        {{-- Icon dan Nama Kategori --}}
                                        <div class="text-sm font-medium text-gray-900">{{ $transaction->category->name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{-- Deskripsi Transaksi --}}
                                    <div class="text-sm text-gray-500">{{ $transaction->description }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    {{-- Jumlah dengan warna berbeda --}}
                                    @if ($transaction->category->type == 'pemasukan')
                                        <span class="text-green-600 font-semibold">+ Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-red-600 font-semibold">- Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{-- Tanggal Transaksi --}}
                                    {{ \Carbon\Carbon::parse($transaction->date)->isoFormat('D MMMM YYYY') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                                    Belum ada transaksi untuk ditampilkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
