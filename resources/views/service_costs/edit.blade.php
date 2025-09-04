<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Service Cost') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex gap-4 mb-4">
                <a href="{{ route('service_costs.index') }}" class="text-sm text-blue-600 underline">&larr; Kembali ke Daftar</a>
                <a href="{{ route('transactions.index') }}" class="text-sm text-blue-600 underline">Halaman Transaksi</a>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <form method="POST" action="{{ route('service_costs.update', $serviceCost) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama</label>
                        <input type="text" name="name" value="{{ old('name', $serviceCost->name) }}" required class="w-full px-4 py-2 border rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex justify-end gap-4">
                        <a href="{{ route('service_costs.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
