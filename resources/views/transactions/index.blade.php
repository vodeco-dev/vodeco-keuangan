@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Transactions</h1>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-transaction')">
        Tambah Transaksi
    </x-primary-button>
</div>

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($transactions as $transaction)
            <tr>
                <td class="px-4 py-2 whitespace-nowrap">{{ $transaction->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-2 whitespace-nowrap">{{ $transaction->category->name }}</td>
                <td class="px-4 py-2">{{ $transaction->description }}</td>
                <td class="px-4 py-2 @if($transaction->category->type === 'income') text-green-600 @else text-red-600 @endif">{{ number_format($transaction->amount, 0, ',', '.') }}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium space-x-2">
                    <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'edit-transaction-{{ $transaction->id }}')" class="text-blue-600 hover:underline">Ubah</button>
                    <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button class="text-red-600 hover:underline" onclick="return confirm('Hapus transaksi ini?')">Hapus</button>
                    </form>
                </td>
            </tr>

            <x-modal name="edit-transaction-{{ $transaction->id }}" focusable>
                <form method="POST" action="{{ route('transactions.update', $transaction) }}" class="p-6">
                    @csrf
                    @method('PUT')
                    <div>
                        <x-input-label for="category_id_{{ $transaction->id }}" value="Kategori" />
                        <select id="category_id_{{ $transaction->id }}" name="category_id" class="mt-1 block w-full">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($transaction->category_id == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="description_{{ $transaction->id }}" value="Deskripsi" />
                        <x-text-input id="description_{{ $transaction->id }}" name="description" class="mt-1 block w-full" value="{{ $transaction->description }}" />
                    </div>
                    <div class="mt-4">
                        <x-input-label for="amount_{{ $transaction->id }}" value="Jumlah" />
                        <x-text-input id="amount_{{ $transaction->id }}" name="amount" type="number" class="mt-1 block w-full" value="{{ $transaction->amount }}" />
                    </div>
                    <div class="mt-6 flex justify-end">
                        <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                        <x-primary-button class="ms-3">Simpan</x-primary-button>
                    </div>
                </form>
            </x-modal>
            @endforeach
        </tbody>
    </table>
</div>

<x-modal name="create-transaction" focusable>
    <form method="POST" action="{{ route('transactions.store') }}" class="p-6">
        @csrf
        <div>
            <x-input-label for="category_id" value="Kategori" />
            <select id="category_id" name="category_id" class="mt-1 block w-full">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mt-4">
            <x-input-label for="description" value="Deskripsi" />
            <x-text-input id="description" name="description" class="mt-1 block w-full" />
        </div>
        <div class="mt-4">
            <x-input-label for="amount" value="Jumlah" />
            <x-text-input id="amount" name="amount" type="number" class="mt-1 block w-full" />
        </div>
        <div class="mt-6 flex justify-end">
            <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
            <x-primary-button class="ms-3">Simpan</x-primary-button>
        </div>
    </form>
</x-modal>
@endsection
