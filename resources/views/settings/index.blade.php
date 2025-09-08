@extends('layouts.app')

@section('content')
    <h2 class="text-3xl font-bold text-gray-800 mb-8">Pengaturan</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        {{-- Kolom 1 --}}
        <div class="space-y-6">
            {{-- Kartu Akun --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Akun</h3>
                <ul class="space-y-3">
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Ubah Profil</span>
                        {{-- Link ke halaman profil yang sudah ada --}}
                        <a href="{{ route('profile.edit') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Ubah</a>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Keamanan (Kata Sandi)</span>
                         {{-- Link ke halaman profil yang sama, bisa di-anchor ke bagian password --}}
                        <a href="{{ route('profile.edit') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Ubah</a>
                    </li>
                </ul>
            </div>

            {{-- Kartu Aplikasi --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Aplikasi</h3>
                <ul class="space-y-3">
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Preferensi Tampilan</span>
                        <a href="{{ route('settings.display') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Kelola</a>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Pengingat</span>
                        <a href="{{ route('settings.notifications') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Atur</a>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Saldo Awal</span>
                        <button class="text-gray-400 text-sm font-medium cursor-not-allowed">Atur</button>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Kolom 2 --}}
        <div class="space-y-6">
            {{-- Kartu Manajemen Data --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Manajemen Data</h3>
                <ul class="space-y-3">
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Ekspor Data</span>
                        <button class="text-gray-400 text-sm font-medium cursor-not-allowed">Ekspor</button>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Impor Data</span>
                        <button class="text-gray-400 text-sm font-medium cursor-not-allowed">Impor</button>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Hapus Semua Data</span>
                        <button class="text-gray-400 text-sm font-medium cursor-not-allowed">Hapus</button>
                    </li>
                    @can('admin')
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Log Aktivitas</span>
                        <a href="{{ route('admin.activity-logs.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Lihat</a>
                    </li>
                    @endcan
                </ul>
            </div>

            {{-- Kartu Tentang Aplikasi --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Tentang Aplikasi</h3>
                <ul class="space-y-3">
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Versi Aplikasi</span>
                        <span class="text-sm text-gray-500">v1.0.0</span>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Kebijakan Privasi</span>
                        <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Lihat</a>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Syarat & Ketentuan</span>
                        <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Lihat</a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Kolom 3 --}}
        <div class="lg:col-span-1 md:col-span-2">
            <div class="bg-white rounded-lg shadow-sm p-6 h-full">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Bantuan & Dukungan</h3>
                <ul class="space-y-3">
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Pusat Bantuan</span>
                        <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Kunjungi</a>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Hubungi Kami</span>
                        <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Kontak</a>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-gray-600">Laporkan Masalah</span>
                        <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Laporkan</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection
