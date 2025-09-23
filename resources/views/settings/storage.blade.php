@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <h2 class="text-3xl font-bold text-gray-800">Penyimpanan Bukti Transaksi</h2>

        <div class="bg-white rounded-lg shadow-sm p-8">
            <form method="POST" action="{{ route('settings.storage.update') }}" class="space-y-6">
                @csrf

                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Pilih Lokasi Penyimpanan</h3>
                    <p class="text-sm text-gray-500 mt-1">Tentukan apakah bukti transaksi akan disimpan di server aplikasi atau di direktori drive eksternal.</p>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="flex items-start gap-3 rounded-lg border p-4 hover:border-blue-400 {{ old('transaction_proof_storage', $transaction_proof_storage) === 'server' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                            <input
                                type="radio"
                                name="transaction_proof_storage"
                                value="server"
                                class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500"
                                {{ old('transaction_proof_storage', $transaction_proof_storage) === 'server' ? 'checked' : '' }}
                            >
                            <span>
                                <span class="block text-sm font-medium text-gray-900">Simpan di Server</span>
                                <span class="block text-xs text-gray-500">File akan disimpan di penyimpanan aplikasi (storage/app/public).</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-lg border p-4 hover:border-blue-400 {{ old('transaction_proof_storage', $transaction_proof_storage) === 'drive' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                            <input
                                type="radio"
                                name="transaction_proof_storage"
                                value="drive"
                                class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500"
                                {{ old('transaction_proof_storage', $transaction_proof_storage) === 'drive' ? 'checked' : '' }}
                            >
                            <span>
                                <span class="block text-sm font-medium text-gray-900">Simpan di Drive</span>
                                <span class="block text-xs text-gray-500">Gunakan path direktori drive yang terhubung ke server (misalnya /mnt/drive/bukti-transaksi).</span>
                            </span>
                        </label>
                    </div>
                    @error('transaction_proof_storage')
                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label for="transaction_proof_server_directory" class="block text-sm font-medium text-gray-700">Direktori di Server</label>
                        <input
                            type="text"
                            id="transaction_proof_server_directory"
                            name="transaction_proof_server_directory"
                            value="{{ old('transaction_proof_server_directory', $transaction_proof_server_directory) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Contoh: transaction-proofs"
                        >
                        <p class="mt-1 text-xs text-gray-500">Direktori relatif dari <code>storage/app/public</code>. Sistem akan membuat struktur {tahun}/{bulan}/{pemasukan|pengeluaran} secara otomatis.</p>
                        @error('transaction_proof_server_directory')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="transaction_proof_drive_directory" class="block text-sm font-medium text-gray-700">Direktori Drive</label>
                        <input
                            type="text"
                            id="transaction_proof_drive_directory"
                            name="transaction_proof_drive_directory"
                            value="{{ old('transaction_proof_drive_directory', $transaction_proof_drive_directory) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Contoh: /mnt/drive/bukti-transaksi"
                        >
                        <p class="mt-1 text-xs text-gray-500">Gunakan path absolut yang dapat diakses server. Struktur folder {tahun}/{bulan}/{pemasukan|pengeluaran} dibuat otomatis.</p>
                        @error('transaction_proof_drive_directory')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="transaction_proof_drive_link" class="block text-sm font-medium text-gray-700">Link Drive (Opsional)</label>
                    <input
                        type="url"
                        id="transaction_proof_drive_link"
                        name="transaction_proof_drive_link"
                        value="{{ old('transaction_proof_drive_link', $transaction_proof_drive_link) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="https://drive.google.com/drive/folders/..."
                    >
                    <p class="mt-1 text-xs text-gray-500">Link akan digunakan untuk membangun URL akses ketika bukti disimpan di drive.</p>
                    @error('transaction_proof_drive_link')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                    <p class="font-semibold">Struktur Folder Otomatis</p>
                    <p>Saat Anda mengunggah bukti, sistem akan menyimpan file pada struktur <code>{tahun}/{bulan}/{pemasukan|pengeluaran}/{nama-file}.jpg</code> sesuai tanggal transaksi dan jenis kategorinya.</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
