@extends('layouts.app')

@section('content')
{{-- Header Halaman --}}
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-8">
    <div class="flex items-center gap-3">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-white leading-tight">
            {{ __($title) }}
        </h2>
        <button type="button" id="toggle-currency-btn" class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 rounded-lg transition-colors" title="Sembunyikan/Tampilkan Nilai Mata Uang">
            <svg id="toggle-currency-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
        </button>
    </div>
</div>

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

    {{-- Kartu Ringkasan Saldo, Pemasukan, dan Pengeluaran --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
        {{-- Kartu Total Saldo --}}
        <div class="p-6 bg-white rounded-2xl shadow-lg">
            <p class="text-sm font-medium text-gray-500">Total Saldo</p>
            <p class="mt-1 text-3xl font-bold text-gray-900 currency-value" data-value="{{ number_format($summary['saldo'] ?? 0, 0, ',', '.') }}">
                Rp {{ number_format($summary['saldo'] ?? 0, 0, ',', '.') }}
            </p>
        </div>
        {{-- Kartu Pemasukan --}}
        <div class="p-6 bg-white rounded-2xl shadow-lg">
            <p class="text-sm font-medium text-gray-500">Pemasukan</p>
            <p class="mt-1 text-3xl font-bold text-green-600 currency-value" data-value="{{ number_format($summary['totalPemasukan'] ?? 0, 0, ',', '.') }}">
                Rp {{ number_format($summary['totalPemasukan'] ?? 0, 0, ',', '.') }}
            </p>
        </div>
        {{-- Kartu Pengeluaran --}}
        <div class="p-6 bg-white rounded-2xl shadow-lg">
            <p class="text-sm font-medium text-gray-500">Pengeluaran</p>
            <p class="mt-1 text-3xl font-bold text-red-600 currency-value" data-value="{{ number_format($summary['totalPengeluaran'] ?? 0, 0, ',', '.') }}">
                Rp {{ number_format($summary['totalPengeluaran'] ?? 0, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Chart Pemasukan dan Pengeluaran --}}
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900">Grafik Pemasukan & Pengeluaran</h3>
            <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-2 mt-4 sm:mt-0">
                <input type="month" name="month" class="border rounded-lg text-sm px-4 py-2" value="{{ $selected_month }}">
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Filter</button>
            </form>
        </div>
        <div class="h-80">
            <canvas id="dashboard-chart"></canvas>
        </div>
        <div class="flex items-center gap-2 mt-4">
            <button type="button" id="toggle-pemasukan-btn" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400">
                <span id="toggle-pemasukan-text">Sembunyikan Pemasukan</span>
            </button>
            <button type="button" id="toggle-pengeluaran-btn" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400">
                <span id="toggle-pengeluaran-text">Sembunyikan Pengeluaran</span>
            </button>
        </div>
    </div>

    {{-- Keadaan Keuangan & Transaksi Terbaru --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
        {{-- Keadaan Keuangan --}}
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 ">Keadaan Keuangan</h3>
                <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-2 mt-4 sm:mt-0">
                    <input type="month" name="month" class="border rounded-lg text-sm px-4 py-2" value="{{ $selected_month }}">
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Filter</button>
                </form>
            </div>

            @php
                $monthName = \Carbon\Carbon::createFromFormat('Y-m', $selected_month)->translatedFormat('F Y');
            @endphp
            <p class="text-sm text-gray-500">Bulan: {{ $monthName }}</p>
            <p class="mt-2 text-lg font-semibold {{ $financial_overview['net'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $financial_overview['net'] >= 0 ? 'Surplus' : 'Defisit' }} <span class="currency-value" data-value="{{ number_format(abs($financial_overview['net']), 0, ',', '.') }}">Rp {{ number_format(abs($financial_overview['net']), 0, ',', '.') }}</span>
            </p>
            <div class="mt-4 space-y-1">
                <p class="text-sm text-gray-600">Pemasukan: <span class="currency-value" data-value="{{ number_format($financial_overview['pemasukan'], 0, ',', '.') }}">Rp {{ number_format($financial_overview['pemasukan'], 0, ',', '.') }}</span></p>
                <p class="text-sm text-gray-600">Pengeluaran: <span class="currency-value" data-value="{{ number_format($financial_overview['pengeluaran'], 0, ',', '.') }}">Rp {{ number_format($financial_overview['pengeluaran'], 0, ',', '.') }}</span></p>
            </div>
            <div class="mt-4">
                @php $change = $financial_overview['percent_change']; @endphp
                @if(!is_null($change))
                    @if($change > 0)
                        <p class="text-sm text-green-600">Naik {{ number_format($change, 2) }}% dari bulan sebelumnya</p>
                    @elseif($change < 0)
                        <p class="text-sm text-red-600">Turun {{ number_format(abs($change), 2) }}% dari bulan sebelumnya</p>
                    @else
                        <p class="text-sm text-gray-600">Tidak berubah dari bulan sebelumnya</p>
                    @endif
                @else
                    <p class="text-sm text-gray-600">Tidak ada data bulan sebelumnya</p>
                @endif
            </div>
        </div>

        {{-- Transaksi Terbaru --}}
        <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Transaksi Terbaru</h3>
            <div class="overflow-x-auto bg-white rounded-2xl shadow-lg">
                <table class="w-full text-left">
                    <thead class="border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase">Tanggal</th>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase">Kategori</th>
                            <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase">Deskripsi</th>
                            @if (!empty($show_user_column))
                                <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase">Pengguna</th>
                            @endif
                            <th class="px-6 py-4 text-xs font-semibold tracking-wider text-gray-500 uppercase text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($recent_transactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($transaction->date)->isoFormat('D MMMM YYYY') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900 ">{{ $transaction->category->name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $transaction->description ?: '-' }}</div>
                                    </td>
                                    @if (!empty($show_user_column))
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ $transaction->user?->name ?? '-' }}</div>
                                        </td>
                                    @endif
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                        @if ($transaction->category->type == 'pemasukan')
                                            <span class="text-green-600 font-semibold currency-value" data-value="{{ number_format($transaction->amount, 0, ',', '.') }}">+ Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-red-600 font-semibold currency-value" data-value="{{ number_format($transaction->amount, 0, ',', '.') }}">- Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ !empty($show_user_column) ? 5 : 4 }}" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                                        Belum ada transaksi untuk ditampilkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Toggle currency visibility
    const toggleCurrencyBtn = document.getElementById('toggle-currency-btn');
    const toggleCurrencyIcon = document.getElementById('toggle-currency-icon');
    let isCurrencyVisible = true;

    if (toggleCurrencyBtn) {
        toggleCurrencyBtn.addEventListener('click', function() {
            isCurrencyVisible = !isCurrencyVisible;
            const currencyElements = document.querySelectorAll('.currency-value');
            
            currencyElements.forEach(function(element) {
                const originalValue = element.getAttribute('data-value');
                const parentElement = element.parentElement;
                const parentText = parentElement ? parentElement.textContent : '';
                const hasPlus = parentText.includes('+') && element.textContent.includes('+');
                const hasMinus = parentText.includes('-') && element.textContent.includes('-');
                
                if (isCurrencyVisible) {
                    // Tampilkan nilai asli
                    if (hasPlus) {
                        element.textContent = '+ Rp ' + originalValue;
                    } else if (hasMinus) {
                        element.textContent = '- Rp ' + originalValue;
                    } else {
                        element.textContent = 'Rp ' + originalValue;
                    }
                } else {
                    // Sembunyikan dengan asterisk
                    const asteriskCount = Math.max(5, Math.min(15, originalValue.length));
                    if (hasPlus) {
                        element.textContent = '+ Rp ' + '*'.repeat(asteriskCount);
                    } else if (hasMinus) {
                        element.textContent = '- Rp ' + '*'.repeat(asteriskCount);
                    } else {
                        element.textContent = 'Rp ' + '*'.repeat(asteriskCount);
                    }
                }
            });

            // Update icon
            if (isCurrencyVisible) {
                toggleCurrencyIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            } else {
                toggleCurrencyIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.29 3.29m0 0L3 3m3.29 3.29L12 12m-5.71-5.71L12 12"></path>';
            }
        });
    }

    const ctx = document.getElementById('dashboard-chart');
    const chartData = @json($chartData ?? ['labels' => [], 'pemasukan' => [], 'pengeluaran' => []]);
    let dashboardChart = null;

    if (ctx && chartData.labels && chartData.labels.length > 0) {
        const canvasBackgroundPlugin = {
            id: 'canvasBackgroundPlugin',
            beforeDraw(chart, args, options) {
                const { ctx, canvas } = chart;
                const { width, height } = canvas;

                ctx.save();
                ctx.globalCompositeOperation = 'destination-over';
                ctx.fillStyle = options?.color || '#ffffff';
                ctx.fillRect(0, 0, width, height);
                ctx.restore();
            }
        };

        // Register plugin jika belum terdaftar
        const pluginAlreadyRegistered = Chart.registry &&
            Chart.registry.plugins &&
            typeof Chart.registry.plugins.get === 'function' &&
            Chart.registry.plugins.get('canvasBackgroundPlugin');

        if (!pluginAlreadyRegistered) {
            Chart.register(canvasBackgroundPlugin);
        }

        try {
            dashboardChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Pemasukan',
                        data: chartData.pemasukan,
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        hidden: false
                    }, {
                        label: 'Pengeluaran',
                        data: chartData.pengeluaran,
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: 'rgba(239, 68, 68, 1)',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        hidden: false
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
                        canvasBackgroundPlugin: {
                            color: '#ffffff'
                        },
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
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });

            // Toggle visibility untuk line Pemasukan
            const togglePemasukanBtn = document.getElementById('toggle-pemasukan-btn');
            const togglePemasukanText = document.getElementById('toggle-pemasukan-text');
            if (togglePemasukanBtn && dashboardChart) {
                togglePemasukanBtn.addEventListener('click', function() {
                    const pemasukanDataset = dashboardChart.data.datasets[0];
                    pemasukanDataset.hidden = !pemasukanDataset.hidden;
                    dashboardChart.update();
                    
                    if (pemasukanDataset.hidden) {
                        togglePemasukanText.textContent = 'Tampilkan Pemasukan';
                        togglePemasukanBtn.classList.remove('bg-green-50', 'border-green-300', 'text-green-700');
                        togglePemasukanBtn.classList.add('bg-gray-100', 'border-gray-300', 'text-gray-700');
                    } else {
                        togglePemasukanText.textContent = 'Sembunyikan Pemasukan';
                        togglePemasukanBtn.classList.remove('bg-gray-100', 'border-gray-300', 'text-gray-700');
                        togglePemasukanBtn.classList.add('bg-green-50', 'border-green-300', 'text-green-700');
                    }
                });
            }

            // Toggle visibility untuk line Pengeluaran
            const togglePengeluaranBtn = document.getElementById('toggle-pengeluaran-btn');
            const togglePengeluaranText = document.getElementById('toggle-pengeluaran-text');
            if (togglePengeluaranBtn && dashboardChart) {
                togglePengeluaranBtn.addEventListener('click', function() {
                    const pengeluaranDataset = dashboardChart.data.datasets[1];
                    pengeluaranDataset.hidden = !pengeluaranDataset.hidden;
                    dashboardChart.update();
                    
                    if (pengeluaranDataset.hidden) {
                        togglePengeluaranText.textContent = 'Tampilkan Pengeluaran';
                        togglePengeluaranBtn.classList.remove('bg-red-50', 'border-red-300', 'text-red-700');
                        togglePengeluaranBtn.classList.add('bg-gray-100', 'border-gray-300', 'text-gray-700');
                    } else {
                        togglePengeluaranText.textContent = 'Sembunyikan Pengeluaran';
                        togglePengeluaranBtn.classList.remove('bg-gray-100', 'border-gray-300', 'text-gray-700');
                        togglePengeluaranBtn.classList.add('bg-red-50', 'border-red-300', 'text-red-700');
                    }
                });
            }
        } catch (error) {
            console.error('Gagal merender grafik dashboard:', error);
        }
    } else {
        // Tampilkan pesan jika tidak ada data
        if (ctx) {
            ctx.parentElement.innerHTML = '<p class="text-center text-gray-500 py-8">Tidak ada data transaksi untuk bulan ini.</p>';
        }
    }
</script>
@endpush
@endsection
