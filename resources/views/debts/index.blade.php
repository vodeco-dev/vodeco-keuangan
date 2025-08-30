@extends('layouts.app')

@section('content')
{{-- Tambahkan 'detailModal' ke dalam x-data untuk mengontrol modal baru --}}
<div x-data="{ addModal: false, paymentModal: false, detailModal: false, selectedDebt: {} }">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Manajemen Hutang & Piutang</h2>
        <button @click="addModal = true" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center gap-2">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            <span>Tambah Catatan Baru</span>
        </button>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Total Pass Through</p>
            <p class="text-2xl font-semibold text-blue-600">Rp{{ number_format($totalPassThrough, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Total Down Payment</p>
            <p class="text-2xl font-semibold text-red-600">Rp{{ number_format($totalDownPayment, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Belum Lunas</p>
            <p class="text-2xl font-semibold text-orange-500">Rp{{ number_format($totalBelumLunas, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Sudah Lunas</p>
            <p class="text-2xl font-semibold text-green-600">Rp{{ number_format($totalLunas, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Main Content Area --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        {{-- Filter & Search Form --}}
        <form method="GET" action="{{ route('debts.index') }}">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-4">
                    <select name="type_filter" onchange="this.form.submit()" class="border-gray-300 rounded-lg text-sm">
                        <option value="">Tipe: Semua</option>
                        <option value="pass_through" {{ request('type_filter') == 'pass_through' ? 'selected' : '' }}>Pass Through</option>
                        <option value="down_payment" {{ request('type_filter') == 'down_payment' ? 'selected' : '' }}>Down Payment</option>
                    </select>
                    <select name="status_filter" onchange="this.form.submit()" class="border-gray-300 rounded-lg text-sm">
                        <option value="">Status: Semua</option>
                        <option value="belum lunas" {{ request('status_filter') == 'belum lunas' ? 'selected' : '' }}>Belum Lunas</option>
                        <option value="lunas" {{ request('status_filter') == 'lunas' ? 'selected' : '' }}>Lunas</option>
                    </select>
                </div>
                <div class="relative">
                    <input name="search" class="pl-10 pr-4 py-2 border rounded-lg text-sm" placeholder="Cari..." type="text" value="{{ request('search') }}">
                    <button type="submit" class="absolute inset-y-0 left-0 pl-3 flex items-center">
                        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Deskripsi</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Pihak Terkait</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Tipe</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Dibayar</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Sisa</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Progres</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Jatuh Tempo</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($debts as $debt)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $debt->description }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $debt->related_party }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($debt->type == 'pass_through')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Pass Through</span>
                            @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Down Payment</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">Rp{{ number_format($debt->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">Rp{{ number_format($debt->paid_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">Rp{{ number_format($debt->remaining_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="{{ $debt->type == 'pass_through' ? 'bg-blue-600' : 'bg-red-600' }} h-2.5 rounded-full" style="width: {{ $debt->progress }}%"></div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $debt->due_date ? \Carbon\Carbon::parse($debt->due_date)->isoFormat('D MMM YYYY') : '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($debt->status == 'lunas')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Lunas</span>
                            @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800">Belum Lunas</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if ($debt->status == 'belum lunas')
                                <button @click="paymentModal = true; selectedDebt = {{ $debt }}" class="text-blue-600 hover:text-blue-900" title="Tambah Pembayaran">
                                    <svg fill="none" height="20" stroke="currentColor" viewBox="0 0 24 24" width="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                </button>
                                @endif

                                {{-- Tombol Detail Riwayat --}}
                                <button @click="detailModal = true; selectedDebt = {{ $debt }}" class="text-gray-500 hover:text-gray-800" title="Lihat Riwayat Pembayaran">
                                    <svg fill="none" height="20" stroke="currentColor" viewBox="0 0 24 24" width="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>

                                <form action="{{ route('debts.destroy', $debt) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus catatan ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-800" title="Hapus">
                                        <svg fill="none" height="20" stroke="currentColor" viewBox="0 0 24 24" width="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-8 text-gray-500">Tidak ada data untuk ditampilkan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Tambah Catatan Baru --}}
    <div x-show="addModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
        <div @click.away="addModal = false" class="bg-white rounded-lg p-8 w-full max-w-md">
            <h3 class="text-2xl font-bold mb-6">Tambah Catatan Baru</h3>
            <form action="{{ route('debts.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <input type="text" name="description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pihak Terkait</label>
                        <input type="text" name="related_party" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipe</label>
                        <select name="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="pass_through">Pass Through (Orang lain berhutang ke saya)</option>
                            <option value="down_payment">Down Payment (Saya berhutang ke orang lain)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Nilai</label>
                        <input type="number" name="amount" step="any" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jatuh Tempo (Opsional)</label>
                        <input type="date" name="due_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-4">
                    <button type="button" @click="addModal = false" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Catat Pembayaran --}}
    <div x-show="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
        <div @click.away="paymentModal = false" class="bg-white rounded-lg p-8 w-full max-w-md">
            <h3 class="text-2xl font-bold mb-2">Catat Pembayaran</h3>
            <p class="text-gray-600 mb-6" x-text="selectedDebt.description"></p>
            <form :action="`/debts/${selectedDebt.id}/pay`" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jumlah Pembayaran</label>
                        <input type="number" name="payment_amount" step="any" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Pembayaran</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                     <div>
                        <label class="block text-sm font-medium text-gray-700">Catatan (Opsional)</label>
                        <textarea name="notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-4">
                    <button type="button" @click="paymentModal = false" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <div x-show="detailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
        <div @click.away="detailModal = false" class="bg-white rounded-lg p-8 w-full max-w-2xl">
            <h3 class="text-2xl font-bold mb-2">Riwayat Pembayaran</h3>
            <p class="text-gray-600 mb-6" x-text="selectedDebt.description"></p>
            
            {{-- Info Ringkas --}}
            <div class="grid grid-cols-3 gap-4 mb-6 text-center">
                <div>
                    <p class="text-sm text-gray-500">Total Nilai</p>
                    <p class="font-semibold" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(selectedDebt.amount)"></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Telah Dibayar</p>
                    <p class="font-semibold text-green-600" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(selectedDebt.paid_amount)"></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Sisa Tagihan</p>
                    <p class="font-semibold text-red-600" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(selectedDebt.remaining_amount)"></p>
                </div>
            </div>

            {{-- Tabel Riwayat Cicilan --}}
            <div class="overflow-y-auto max-h-64 border rounded-lg">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">Jumlah</th>
                            <th class="px-4 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y bg-white">
                        <template x-if="selectedDebt.payments && selectedDebt.payments.length > 0">
                             <template x-for="payment in selectedDebt.payments" :key="payment.id">
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-500" x-text="new Date(payment.payment_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })"></td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(payment.amount)"></td>
                                    <td class="px-4 py-3 text-sm text-gray-500" x-text="payment.notes || '-'"></td>
                                </tr>
                            </template>
                        </template>
                        <template x-if="!selectedDebt.payments || selectedDebt.payments.length === 0">
                            <tr>
                                <td colspan="3" class="text-center py-6 text-gray-500">Belum ada pembayaran yang tercatat.</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" @click="detailModal = false" class="px-4 py-2 bg-gray-200 rounded-lg">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection