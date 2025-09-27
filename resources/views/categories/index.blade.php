<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Kelola Kategori') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ editModalOpen: false, editCategory: {} }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Form Tambah Kategori --}}
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Tambah Kategori Baru</h3>
                <form action="{{ route('categories.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                    @csrf
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2" for="category-name">Nama Kategori</label>
                        <input name="name" class="w-full px-4 py-2 border rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" id="category-name" placeholder="Masukkan Kategori" type="text" value="{{ old('name') }}" required/>
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2" for="category-type">Tipe Kategori</label>
                        <select name="type" class="w-full px-4 py-2 border rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" id="category-type" required>
                            <option value="pemasukan" @if(old('type') == 'pemasukan') selected @endif>Pemasukan</option>
                            <option value="pengeluaran" @if(old('type') == 'pengeluaran') selected @endif>Pengeluaran</option>
                        </select>
                        @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-1">
                        <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            Tambah Kategori
                        </button>
                    </div>
                </form>
            </div>

            {{-- Notifikasi Sukses atau Error --}}
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

            {{-- Daftar Kategori --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Kategori Pemasukan -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-xl font-semibold text-green-600 mb-4">Kategori Pemasukan</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="border-b">
                                <tr>
                                    <th class="px-6 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Nama</th>
                                    <th class="px-6 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($pemasukan as $category)
                                    <tr>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $category->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                            <div class="flex justify-center items-center gap-4">
                                                <button @click="editModalOpen = true; editCategory = {{ $category->toJson() }}" class="text-blue-600 hover:text-blue-900">Edit</button>
                                                <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus kategori ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada kategori pemasukan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Kategori Pengeluaran -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-xl font-semibold text-red-600 mb-4">Kategori Pengeluaran</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                           <thead class="border-b">
                                <tr>
                                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Nama</th>
                                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($pengeluaran as $category)
                                     <tr>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $category->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                            <div class="flex justify-center items-center gap-4">
                                                <button @click="editModalOpen = true; editCategory = {{ $category->toJson() }}" class="text-blue-600 hover:text-blue-900">Edit</button>
                                                <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus kategori ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada kategori pengeluaran.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Edit -->
        <div x-show="editModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
            <div @click.away="editModalOpen = false" class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <h3 class="text-xl font-semibold mb-4">Edit Kategori</h3>
                <form :action="'/categories/' + editCategory.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label for="edit-name" class="block text-sm font-medium text-gray-700">Nama Kategori</label>
                        <input type="text" name="name" id="edit-name" :value="editCategory.name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div class="mb-4">
                        <label for="edit-type" class="block text-sm font-medium text-gray-700">Tipe</label>
                        <select name="type" id="edit-type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="pemasukan" :selected="editCategory.type === 'pemasukan'">Pemasukan</option>
                            <option value="pengeluaran" :selected="editCategory.type === 'pengeluaran'">Pengeluaran</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-4 mt-6">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-gray-200 text-gray-800 dark:text-white rounded-lg hover:bg-gray-300">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
