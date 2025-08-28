@extends('layouts.app')

@section('content')
  <h2 class="text-3xl font-bold mb-8">{{ $title }}</h2>

  <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
    <div class="p-6 bg-white rounded-lg shadow-sm">
      <p class="text-sm text-gray-500">Total Saldo</p>
      <p class="mt-1 text-3xl font-bold text-gray-900">${{ number_format($saldo, 2) }}</p>
    </div>
    <div class="p-6 bg-white rounded-lg shadow-sm">
      <p class="text-sm text-gray-500">Pemasukan</p>
      <p class="mt-1 text-3xl font-bold text-green-600">${{ number_format($pemasukan, 2) }}</p>
    </div>
    <div class="p-6 bg-white rounded-lg shadow-sm">
      <p class="text-sm text-gray-500">Pengeluaran</p>
      <p class="mt-1 text-3xl font-bold text-red-600">${{ number_format($pengeluaran, 2) }}</p>
    </div>
  </div>

  {{-- tambahkan chart & tabel transaksi di sini --}}
@endsection
