<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Klien') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('clients.update', $client) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Nama Klien</label>
                        <input type="text" name="name" value="{{ old('name', $client->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        @error('name')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex justify-end">
                        <a href="{{ route('clients.index') }}" class="text-gray-600 mr-4">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Perbarui</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
