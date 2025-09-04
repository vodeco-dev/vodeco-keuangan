<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transaksi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Bagian Summary Cards --}}
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
                <div class="p-6 bg-white rounded-lg shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Total Pemasukan</p>
                    <p class="mt-1 text-3xl font-bold text-green-600">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</p>
                </div>
                <div class="p-6 bg-white rounded-lg shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Total Pengeluaran</p>
                    <p class="mt-1 text-3xl font-bold text-red-600">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</p>
                </div>
                <div class="p-6 bg-white rounded-lg shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Saldo</p>
                    <p class="mt-1 text-3xl font-bold text-gray-800">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
                </div>
            </div>

            {{-- Bagian Tabel dan Filter --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                {{-- Form Filter --}}
                <form action="{{ route('transactions.index') }}" method="GET">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                        <div class="flex-1">
                            <input name="search" class="w-full pl-4 pr-4 py-2 border rounded-lg text-sm" placeholder="Cari transaksi..." type="text" value="{{ request('search') }}">
                        </div>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <input name="date" class="border rounded-lg text-sm px-4 py-2" type="date" value="{{ request('date') }}">
                            <select name="category_id" class="border rounded-lg text-sm px-4 py-2">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="type" class="border rounded-lg text-sm px-4 py-2">
                                <option value="">Semua Tipe</option>
                                <option value="pemasukan" {{ request('type') == 'pemasukan' ? 'selected' : '' }}>Pemasukan</option>
                                <option value="pengeluaran" {{ request('type') == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                            </select>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                Filter
                            </button>
                        </div>
                        <a href="{{ route('transactions.create') }}" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            <span>Tambah Transaksi</span>
                        </a>
                    </div>
                </form>

                {{-- Tabel Transaksi --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="border-b">
                            <tr>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Deskripsi</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Jumlah</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($transaction->date)->isoFormat('D MMM YYYY') }}</td>
                                    
                                    {{-- INI BAGIAN YANG DIPERBAIKI --}}
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $transaction->category?->name ?? 'Tanpa Kategori' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $transaction->description }}</td>
                                    
                                    {{-- INI JUGA BAGIAN YANG DIPERBAIKI --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-medium @if($transaction->category?->type == 'pemasukan') text-green-600 @else text-red-600 @endif">
                                        {{ $transaction->category?->type == 'pemasukan' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex justify-center items-center gap-2">
                                            <a href="#" class="text-blue-600 hover:text-blue-900">Edit</a>
                                            <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" onsubmit="return confirm('Yakin hapus?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        Belum ada transaksi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginasi --}}
                <div class="mt-4">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
