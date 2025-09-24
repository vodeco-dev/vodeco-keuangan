@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <h2 class="text-3xl font-bold text-gray-800">Penyimpanan Bukti Transaksi</h2>

        <div class="bg-white rounded-lg shadow-sm p-8">
            <form method="POST" action="{{ route('settings.storage.update') }}" class="space-y-6">
                @csrf

                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Lokasi Penyimpanan</h3>
                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <span class="block text-sm font-medium text-gray-900">Penyimpanan Server Aktif</span>
                        <span class="block text-xs text-gray-500">Semua bukti transaksi akan disimpan secara lokal di dalam server aplikasi. Opsi penyimpanan eksternal telah dinonaktifkan.</span>
                    </div>
                </div>

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
