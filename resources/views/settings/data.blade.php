@extends('layouts.app')

@section('content')
    <div class="max-w-2xl mx-auto">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-8">Manajemen Data</h2>

        {{-- Kartu Ekspor Data --}}
        <div class="bg-white rounded-lg shadow-sm p-8">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Ekspor Data Transaksi</h3>
            <p class="text-gray-600 mb-6">Pilih rentang tanggal dan format file untuk mengekspor data transaksi Anda.</p>

            <form method="POST" action="{{ route('settings.export') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Input Tanggal Mulai --}}
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                        <input type="date" name="start_date" id="start_date" value="{{ now()->startOfMonth()->format('Y-m-d') }}" class="w-full border rounded-lg text-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Input Tanggal Selesai --}}
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                        <input type="date" name="end_date" id="end_date" value="{{ now()->endOfMonth()->format('Y-m-d') }}" class="w-full border rounded-lg text-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                {{-- Pilihan Format --}}
                <div class="mt-6">
                    <label for="format" class="block text-sm font-medium text-gray-700 mb-1">Format File</label>
                    <select name="format" id="format" class="w-full border rounded-lg text-sm px-4 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="xlsx">Excel (.xlsx)</option>
                        <option value="csv">CSV (.csv)</option>
                    </select>
                </div>

                {{-- Tombol Aksi --}}
                <div class="mt-8 text-right">
                    <button type="submit" class="px-6 py-3 font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" x2="12" y1="15" y2="3"></line>
                        </svg>
                        <span>Ekspor Data</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
