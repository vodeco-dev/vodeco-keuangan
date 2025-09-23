<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transaksi') }}
        </h2>
    </x-slot>

    @php
        $isAdmin = auth()->user()?->role === \App\Enums\Role::ADMIN;
    @endphp

    <div class="py-12" @unless($isAdmin) x-data="{
        showReasonModal: false,
        reason: '',
        error: '',
        form: null,
        transactionDescription: '',
        init() {
            this.$watch('showReasonModal', value => {
                document.body.classList.toggle('overflow-y-hidden', value);
            });
        },
        open(event) {
            this.error = '';
            this.reason = '';
            this.form = event.currentTarget.closest('form');
            this.transactionDescription = event.currentTarget.dataset.description || 'Tanpa deskripsi';
            this.showReasonModal = true;
            this.$nextTick(() => this.$refs.reasonTextarea?.focus());
        },
        close() {
            this.showReasonModal = false;
            this.reason = '';
            this.error = '';
            this.transactionDescription = '';
            this.form = null;
        },
        submitReason() {
            if (!this.reason.trim()) {
                this.error = 'Alasan penghapusan wajib diisi.';
                this.$nextTick(() => this.$refs.reasonTextarea?.focus());
                return;
            }

            const form = this.form;
            if (form) {
                const reasonInput = form.querySelector('input[name=\'reason\']');
                if (reasonInput) {
                    reasonInput.value = this.reason.trim();
                }

                this.showReasonModal = false;
                this.reason = '';
                this.form = null;
                this.transactionDescription = '';
                this.error = '';
                form.submit();
            }
        }
    }" @endunless>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Bagian Summary Cards --}}
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
                <div class="p-6 bg-white rounded-lg shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Total Pemasukan</p>
                    <p class="mt-1 text-3xl font-bold text-green-600">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</p>
                </div>
                <div class="p-6 bg-white rounded-lg shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Total Pengeluaran</p>
                    <p class="mt-1 text-3xl font-bold text-red-600">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</p>
                </div>
                <div class="p-6 bg-white rounded-lg shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Saldo</p>
                    <p class="mt-1 text-3xl font-bold text-gray-800">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
                </div>
            </div>

            {{-- Bagian Tabel dan Filter --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                {{-- Form Filter --}}
                <form action="{{ route('transactions.index') }}" method="GET">
                    <div class="flex flex-col gap-6 mb-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div class="flex-1">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari Transaksi</label>
                                <input id="search" name="search" class="w-full pl-4 pr-4 py-2 border rounded-lg text-sm" placeholder="Cari transaksi..." type="text" value="{{ request('search') }}">
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                    Terapkan Filter
                                </button>
                                <a href="{{ route('transactions.index') }}" class="px-4 py-2 text-sm font-medium text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50">
                                    Reset
                                </a>
                                <a href="{{ route('transactions.create') }}" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                    <span>Tambah Transaksi</span>
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                                <select id="month" name="month" class="w-full border rounded-lg text-sm px-4 py-2">
                                    <option value="">Semua Bulan</option>
                                    @foreach (range(1, 12) as $monthNumber)
                                        @php
                                            $monthLabel = \Carbon\Carbon::createFromDate(null, $monthNumber, 1)->locale('id')->translatedFormat('F');
                                        @endphp
                                        <option value="{{ $monthNumber }}" {{ (string) request('month') === (string) $monthNumber ? 'selected' : '' }}>
                                            {{ $monthLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                                @php
                                    $yearOptions = $availableMonths->pluck('year')->unique()->sortDesc();
                                    if ($yearOptions->isEmpty()) {
                                        $yearOptions = collect([now()->year]);
                                    } elseif (!$yearOptions->contains(now()->year)) {
                                        $yearOptions->prepend(now()->year);
                                    }
                                @endphp
                                <select id="year" name="year" class="w-full border rounded-lg text-sm px-4 py-2">
                                    <option value="">Semua Tahun</option>
                                    @foreach ($yearOptions as $yearOption)
                                        <option value="{{ $yearOption }}" {{ (string) request('year') === (string) $yearOption ? 'selected' : '' }}>
                                            {{ $yearOption }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Rentang Mulai</label>
                                <input id="start_date" name="start_date" class="w-full border rounded-lg text-sm px-4 py-2" type="date" value="{{ request('start_date') }}">
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Rentang Selesai</label>
                                <input id="end_date" name="end_date" class="w-full border rounded-lg text-sm px-4 py-2" type="date" value="{{ request('end_date') }}">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Spesifik</label>
                                <input id="date" name="date" class="w-full border rounded-lg text-sm px-4 py-2" type="date" value="{{ request('date') }}">
                            </div>
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                                <select id="category_id" name="category_id" class="w-full border rounded-lg text-sm px-4 py-2">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipe Transaksi</label>
                                <select id="type" name="type" class="w-full border rounded-lg text-sm px-4 py-2">
                                    <option value="">Semua Tipe</option>
                                    <option value="pemasukan" {{ request('type') == 'pemasukan' ? 'selected' : '' }}>Pemasukan</option>
                                    <option value="pengeluaran" {{ request('type') == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <p class="text-xs text-gray-500">Pilih bulan/tahun atau gunakan rentang tanggal untuk melihat histori tertentu.</p>
                            </div>
                        </div>
                    </div>
                </form>

                @if ($availableMonths->isNotEmpty())
                    <div class="mb-6">
                        <p class="text-sm font-medium text-gray-700 mb-2">Histori Bulan</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($availableMonths as $monthItem)
                                @php
                                    $isActive = (string) request('month') === (string) $monthItem['month'] && (string) request('year') === (string) $monthItem['year'];
                                    $historyQuery = array_merge(
                                        request()->except(['page', 'month', 'year', 'start_date', 'end_date', 'date']),
                                        ['month' => $monthItem['month'], 'year' => $monthItem['year']]
                                    );
                                @endphp
                                <a href="{{ route('transactions.index', $historyQuery) }}" class="px-3 py-1 rounded-full text-sm font-medium border {{ $isActive ? 'bg-blue-600 text-white border-blue-600' : 'text-blue-600 border-blue-200 hover:border-blue-400 hover:bg-blue-50' }}">
                                    {{ $monthItem['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Tabel Transaksi --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="border-b">
                            <tr>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Pengguna</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Deskripsi</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Jumlah</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($transaction->date)->isoFormat('D MMM YYYY') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $transaction->user->name }}</td>
                                    
                                    {{-- INI BAGIAN YANG DIPERBAIKI --}}
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $transaction->category?->name ?? 'Tanpa Kategori' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $transaction->description }}</td>
                                    
                                    {{-- INI JUGA BAGIAN YANG DIPERBAIKI --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-medium @if($transaction->category?->type == 'pemasukan') text-green-600 @else text-red-600 @endif">
                                        {{ $transaction->category?->type == 'pemasukan' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex justify-center items-center gap-2">
                                            <a href="{{ route('transactions.edit', $transaction) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                            @if ($isAdmin)
                                                <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" onsubmit="return confirm('Yakin hapus?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                                </form>
                                            @else
                                                <form action="{{ route('transactions.destroy', $transaction) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="reason" value="">
                                                    <button
                                                        type="button"
                                                        class="text-red-600 hover:text-red-900"
                                                        data-description="{{ $transaction->description ?: 'Tanpa Deskripsi' }}"
                                                        @click.prevent="open($event)"
                                                    >
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        Belum ada transaksi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginasi --}}
                <div class="mt-4">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>

        @unless($isAdmin)
            <div
                x-show="showReasonModal"
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 px-4 py-6"
                style="display: none;"
                @keydown.escape.window="close()"
            >
                <div class="absolute inset-0" @click="close()"></div>
                <div class="relative z-10 w-full max-w-lg rounded-lg bg-white p-6 shadow-xl" @click.stop>
                    <h3 class="text-lg font-semibold text-gray-900">Ajukan Penghapusan Transaksi</h3>
                    <p class="mt-2 text-sm text-gray-600" x-text="transactionDescription ? 'Transaksi: ' + transactionDescription : 'Transaksi tanpa deskripsi.'"></p>

                    <form class="mt-4 space-y-4" @submit.prevent="submitReason">
                        <div>
                            <label for="deletion-reason" class="block text-sm font-medium text-gray-700">Alasan Penghapusan</label>
                            <textarea
                                id="deletion-reason"
                                rows="4"
                                x-ref="reasonTextarea"
                                x-model="reason"
                                class="mt-1 w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Jelaskan mengapa transaksi ini perlu dihapus..."
                                required
                            ></textarea>
                            <p class="mt-2 text-sm text-red-600" x-show="error" x-text="error"></p>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="close()">Batal</button>
                            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Kirim Permintaan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endunless
    </div>
</x-app-layout>
