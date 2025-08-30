<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Proyek') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('projects.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Nama Proyek</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        @error('name')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Klien</label>
                        <select name="client_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="">-- Pilih Klien --</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex justify-end">
                        <a href="{{ route('projects.index') }}" class="text-gray-600 mr-4">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
