@extends('layouts.app')

@section('content')
<div class="flex justify-between items-center mb-8">
    <h2 class="text-3xl font-bold text-gray-800">Laporan Keuangan</h2>
    <div class="flex items-center gap-4">
        {{-- Form Filter Tanggal --}}
        <form method="GET" action="{{ route('reports.index') }}" class="flex items-center gap-2">
            <input type="date" name="start_date" value="{{ $startDate }}" class="border rounded-lg text-sm px-4 py-2">
            <span class="text-gray-500">to</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="border rounded-lg text-sm px-4 py-2">
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Filter</button>
        </form>

        {{-- Dropdown untuk Unduh Laporan --}}
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" x2="12" y1="15" y2="3"></line>
                </svg>
                <span>Unduh Laporan</span>
            </button>
            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                <a href="{{ route('reports.export', ['start_date' => $startDate, 'end_date' => $endDate, 'format' => 'xlsx']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Excel (.xlsx)</a>
                <a href="{{ route('reports.export', ['start_date' => $startDate, 'end_date' => $endDate, 'format' => 'csv']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">CSV (.csv)</a>
            </div>
        </div>
    </div>
</div>

{{-- Kartu Ringkasan --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-sm p-6 flex items-center gap-4">
        <div class="p-3 rounded-full bg-green-100 text-green-600">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Pemasukan</p>
            <p class="text-2xl font-semibold text-gray-900">Rp{{ number_format($totalPemasukan, 0, ',', '.') }}</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-6 flex items-center gap-4">
        <div class="p-3 rounded-full bg-red-100 text-red-600">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
            </svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Pengeluaran</p>
            <p class="text-2xl font-semibold text-gray-900">Rp{{ number_format($totalPengeluaran, 0, ',', '.') }}</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-6 flex items-center gap-4">
        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Selisih</p>
            <p class="text-2xl font-semibold {{ $selisih >= 0 ? 'text-green-600' : 'text-red-600' }}">Rp{{ number_format($selisih, 0, ',', '.') }}</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-6 flex items-center gap-4">
        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 8-8" />
            </svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Agency Gross Income</p>
            <p class="text-2xl font-semibold text-gray-900">Rp{{ number_format($agencyGrossIncome, 0, ',', '.') }}</p>
        </div>
    </div>
</div>

{{-- Grafik --}}
<div class="bg-white rounded-lg shadow-sm p-6 mb-8">
    <h3 class="text-xl font-semibold text-gray-900 mb-4">Grafik Pemasukan vs Pengeluaran</h3>
    <div class="h-80"><canvas id="financial-chart"></canvas></div>
</div>


{{-- Tabel Rincian Transaksi --}}
<div class="bg-white rounded-lg shadow-sm p-6">
    <h3 class="text-xl font-semibold text-gray-900 mb-4">Rincian Transaksi</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="border-b">
                <tr>
                    <th class="px-6 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Tanggal</th>
                    <th class="px-6 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Kategori</th>
                    <th class="px-6 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Deskripsi</th>
                    <th class="px-6 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($transactions as $transaction)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">{{ \Carbon\Carbon::parse($transaction->date)->isoFormat('D MMM YYYY') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $transaction->category->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $transaction->description }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right {{ $transaction->category->type == 'pemasukan' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $transaction->category->type == 'pemasukan' ? '+' : '-' }} Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-8 text-gray-500">Tidak ada transaksi pada rentang tanggal ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Script untuk Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('financial-chart');
    const chartData = @json($chartData);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Pemasukan',
                data: chartData.pemasukan,
                backgroundColor: 'rgba(16, 185, 129, 0.6)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 1
            }, {
                label: 'Pengeluaran',
                data: chartData.pengeluaran,
                backgroundColor: 'rgba(239, 68, 68, 0.6)',
                borderColor: 'rgba(239, 68, 68, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp' + value.toLocaleString('id-ID');
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'Rp' + context.parsed.y.toLocaleString('id-ID');
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
</script>
@endsection
