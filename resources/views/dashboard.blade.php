@extends('layouts.app')

@section('content')
    {{-- Header Halaman --}}
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-8">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __($title) }}
        </h2>
    </div>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        {{-- Kartu Ringkasan Saldo, Pemasukan, dan Pengeluaran --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
            {{-- Kartu Total Saldo --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Saldo</p>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Rp {{ number_format($saldo, 0, ',', '.') }}
                </p>
            </div>
            {{-- Kartu Pemasukan --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pemasukan</p>
                <p class="mt-1 text-3xl font-bold text-green-600 dark:text-green-500">
                    Rp {{ number_format($pemasukan, 0, ',', '.') }}
                </p>
            </div>
            {{-- Kartu Pengeluaran --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pengeluaran</p>
                <p class="mt-1 text-3xl font-bold text-red-600 dark:text-red-500">
                    Rp {{ number_format($pengeluaran, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- Grafik Tren Bulanan --}}
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Tren Pemasukan & Pengeluaran</h3>
                <div class="flex items-center gap-2 mt-4 sm:mt-0">
                    <button class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        Filter
                    </button>
                </div>
            </div>
            {{-- Visualisasi Chart (Data Statis) --}}
            <div class="relative h-96">
                <div class="absolute bottom-0 left-0 w-full h-full flex items-end space-x-4 px-4 pb-8">
                    <div class="flex flex-col items-center flex-1 h-full"><div class="w-full h-full flex flex-col justify-end"><div class="flex items-end w-full gap-2"><div class="w-1/2 h-3/5 bg-green-400 rounded-t-md"></div><div class="w-1/2 h-2/5 bg-red-400 rounded-t-md"></div></div></div><p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Jul</p></div>
                    <div class="flex flex-col items-center flex-1 h-full"><div class="w-full h-full flex flex-col justify-end"><div class="flex items-end w-full gap-2"><div class="w-1/2 h-4/5 bg-green-400 rounded-t-md"></div><div class="w-1/2 h-3/5 bg-red-400 rounded-t-md"></div></div></div><p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Ags</p></div>
                    <div class="flex flex-col items-center flex-1 h-full"><div class="w-full h-full flex flex-col justify-end"><div class="flex items-end w-full gap-2"><div class="w-1/2 h-2/5 bg-green-400 rounded-t-md"></div><div class="w-1/2 h-1/5 bg-red-400 rounded-t-md"></div></div></div><p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Sep</p></div>
                    <div class="flex flex-col items-center flex-1 h-full"><div class="w-full h-full flex flex-col justify-end"><div class="flex items-end w-full gap-2"><div class="w-1/2 h-3/4 bg-green-400 rounded-t-md"></div><div class="w-1/2 h-2/4 bg-red-400 rounded-t-md"></div></div></div><p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Okt</p></div>
                    <div class="flex flex-col items-center flex-1 h-full"><div class="w-full h-full flex flex-col justify-end"><div class="flex items-end w-full gap-2"><div class="w-1/2 h-1/2 bg-green-400 rounded-t-md"></div><div class="w-1/2 h-2/5 bg-red-400 rounded-t-md"></div></div></div><p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nov</p></div>
                    <div class="flex flex-col items-center flex-1 h-full"><div class="w-full h-full flex flex-col justify-end"><div class="flex items-end w-full gap-2"><div class="w-1/2 h-5/6 bg-green-400 rounded-t-md"></div><div class="w-1/2 h-4/6 bg-red-400 rounded-t-md"></div></div></div><p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Des</p></div>
                    <div class="flex flex-col items-center flex-1 h-full"><div class="w-full h-full flex flex-col justify-end"><div class="flex items-end w-full gap-2"><div class="w-1/2 h-4/6 bg-green-400 rounded-t-md"></div><div class="w-1/2 h-3/6 bg-red-400 rounded-t-md"></div></div></div><p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Jan</p></div>
                </div>
            </div>
            <div class="flex justify-center mt-4 gap-6">
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-green-400"></div><span class="text-sm text-gray-500 dark:text-gray-400">Pemasukan</span></div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-red-400"></div><span class="text-sm text-gray-500 dark:text-gray-400">Pengeluaran</span></div>
            </div>
        </div>

        {{-- Tabel Transaksi Terbaru --}}
        <div class="mt-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Transaksi Terbaru</h3>
            <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-2xl shadow-lg">
                <table class="w-full text-left">
                    <thead class="border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 dark:text-gray-400 uppercase">Tanggal</th>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 dark:text-gray-400 uppercase">Kategori</th>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 dark:text-gray-400 uppercase">Deskripsi</th>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 dark:text-gray-400 uppercase text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                        {{-- Data Tabel Statis --}}
                        <tr><td class="px-6 py-4 whitespace-nowrap">2024-01-15</td><td class="px-6 py-4 whitespace-nowrap">Belanja</td><td class="px-6 py-4 whitespace-nowrap">Supermarket</td><td class="px-6 py-4 text-right text-red-600 whitespace-nowrap">-Rp 120.500</td></tr>
                        <tr><td class="px-6 py-4 whitespace-nowrap">2024-01-14</td><td class="px-6 py-4 whitespace-nowrap">Gaji</td><td class="px-6 py-4 whitespace-nowrap">Gaji Bulanan</td><td class="px-6 py-4 text-right text-green-600 whitespace-nowrap">Rp 5.000.000</td></tr>
                        <tr><td class="px-6 py-4 whitespace-nowrap">2024-01-12</td><td class="px-6 py-4 whitespace-nowrap">Makan</td><td class="px-6 py-4 whitespace-nowrap">Restoran</td><td class="px-6 py-4 text-right text-red-600 whitespace-nowrap">-Rp 75.250</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
