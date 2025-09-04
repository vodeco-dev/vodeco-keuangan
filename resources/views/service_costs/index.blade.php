<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Service Cost') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <a href="{{ route('transactions.index') }}" class="text-sm text-blue-600 underline mb-4 inline-block">
                &larr; Kembali ke Transaksi
            </a>

            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Tambah Service Cost</h3>
                <form action="{{ route('service_costs.store') }}" method="POST" class="flex flex-col md:flex-row gap-4 items-end">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-2 border rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Tambah</button>
                    </div>
                </form>
            </div>

            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-sm p-6">
                <table class="w-full text-left">
                    <thead class="border-b">
                        <tr>
                            <th class="px-6 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Nama</th>
                            <th class="px-6 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($serviceCosts as $serviceCost)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $serviceCost->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <div class="flex justify-center items-center gap-4">
                                        <a href="{{ route('service_costs.edit', $serviceCost) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                        <form action="{{ route('service_costs.destroy', $serviceCost) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus service cost ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada service cost.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
