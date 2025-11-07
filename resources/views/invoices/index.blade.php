<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Invoices') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="invoicePayments({ defaultDate: '{{ now()->toDateString() }}' })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div x-show="lightboxOpen" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/70 p-6" @keydown.window.escape="closeLightbox()">
                <div class="absolute inset-0" @click="closeLightbox()"></div>
                <div class="relative z-10 w-full max-w-3xl">
                    <img :src="lightboxImageUrl" alt="Bukti Pembayaran" class="max-h-[80vh] w-full rounded-lg bg-white object-contain p-4">
                    <button type="button" class="absolute -right-3 -top-3 flex h-10 w-10 items-center justify-center rounded-full bg-white text-2xl font-semibold text-gray-700 shadow-lg" @click="closeLightbox()">&times;</button>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('access_code_status'))
                    <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                        {{ session('access_code_status') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('access_code_generated'))
                    @php
                        $generated = session('access_code_generated');
                    @endphp
                    <div class="mb-4 rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700">
                        <p class="font-semibold">Kode akses baru berhasil dibuat.</p>
                        <p class="mt-2"><span class="font-semibold">Peran:</span> {{ $generated['role'] }}</p>
                        @if (! empty($generated['user']))
                            <p><span class="font-semibold">Pengguna:</span> {{ $generated['user'] }}</p>
                        @endif
                        <p class="mt-2">
                            <span class="font-semibold">Kode:</span>
                            <span class="font-mono text-base">{{ $generated['code'] }}</span>
                        </p>
                        <p class="mt-2 text-xs text-blue-600">Sampaikan kode ini secara aman. Kode hanya dapat digunakan satu kali.</p>
                    </div>
                @endif

                @if ($accessCodeRole)
                    <div class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                        <h3 class="text-sm font-semibold text-yellow-800">Verifikasi kode akses diperlukan</h3>
                        <p class="mt-2 text-sm text-yellow-700">Masukkan kode akses sekali pakai yang valid untuk peran {{ $accessCodeRole->label() }} sebelum mengakses tab terkait.</p>
                        <form method="POST" action="{{ route('access-codes.verify') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                            @csrf
                            <div class="flex-1">
                                <label for="code" class="block text-sm font-medium text-yellow-800">Kode Akses</label>
                                <input type="text" name="code" id="code" required autofocus
                                    class="mt-1 block w-full rounded-md border-yellow-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                    placeholder="contoh: uuid:KODE123">
                                @error('code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="sm:w-auto">
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-yellow-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-700">
                                    Verifikasi
                                </button>
                            </div>
                        </form>
                        <p class="mt-2 text-xs text-yellow-700">Hubungi admin untuk menerima kode akses terbaru bila diperlukan.</p>
                    </div>
                @endif

                @php
                    $unlockedTabs = collect($tabStates)->filter(fn ($tab) => $tab['unlocked']);
                @endphp
                <div>
                    @if ($unlockedTabs->isNotEmpty())
                        <div class="border-b border-gray-200 mb-6">
                            <nav class="-mb-px flex flex-wrap gap-2" aria-label="Tabs">
                                @foreach ($tabStates as $key => $tab)
                                    @if ($tab['unlocked'])
                                        <a href="{{ route('invoices.index', ['tab' => $key] + Arr::except($filters, ['range', 'type', 'filter_date'])) }}"
                                           class="whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium focus:outline-none {{ $defaultTab === $key ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                            {{ $tab['label'] }}
                                        </a>
                                    @endif
                                @endforeach
                            </nav>
                        </div>
                    @else
                        <div class="mb-6 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                            Tidak ada tab yang dapat ditampilkan. Pastikan Anda memiliki kode akses yang valid atau hubungi administrator.
                        </div>
                    @endif

                    @if ($tabStates['needs-confirmation']['unlocked'] && $defaultTab === 'needs-confirmation')
                    <div>
                        <div class="mb-6 flex flex-wrap items-center gap-4">
                            <form action="{{ route('invoices.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
                                <input type="hidden" name="tab" value="needs-confirmation">
                                <div>
                                    <label for="filter_date_needs_confirmation" class="text-sm font-medium text-gray-600">Tanggal:</label>
                                    <input type="date" name="filter_date" id="filter_date_needs_confirmation" value="{{ $filters['filter_date'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-600">Tipe:</span>
                                    <div class="mt-1 flex items-center gap-2">
                                        <a href="{{ route('invoices.index', ['tab' => 'needs-confirmation', 'type' => 'dp'] + Arr::except($filters, 'type')) }}" class="px-3 py-1 text-sm rounded-md {{ ($filters['type'] ?? '') === 'dp' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}">Down Payment</a>
                                        <a href="{{ route('invoices.index', ['tab' => 'needs-confirmation', 'type' => 'lunas'] + Arr::except($filters, 'type')) }}" class="px-3 py-1 text-sm rounded-md {{ ($filters['type'] ?? '') === 'lunas' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}">Bayar Lunas</a>
                                    </div>
                                </div>
                                <button type="submit" class="rounded-md bg-blue-600 p-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M1 2h14v2L9 9v7l-2-2V9L1 4V2zm0-2h14v1H1V0z"/></svg>
                                </button>
                                <a href="{{ route('invoices.index', ['tab' => 'needs-confirmation']) }}" class="rounded-md bg-red-500 p-2 text-sm text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><g><path d="M8 11a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2H8Z"/><path fill-rule="evenodd" d="M23 12c0 6.075-4.925 11-11 11S1 18.075 1 12S5.925 1 12 1s11 4.925 11 11Zm-2 0a9 9 0 1 1-18 0a9 9 0 0 1 18 0Z" clip-rule="evenodd"/></g></svg>
                                </a>
                            </form>
                        </div>

                        <!-- Bulk Actions Bar -->
                        <div x-show="bulkActions.hasSelection()" x-cloak class="mb-4 flex flex-wrap items-center gap-3 rounded-md bg-blue-50 border border-blue-200 p-3">
                            <span class="text-sm font-medium text-blue-900">
                                <span x-text="bulkActions.getSelectedCount()"></span> invoice dipilih
                            </span>
                            <div class="flex items-center gap-2">
                                <button type="button" 
                                    @click="bulkActions.executeBulkAction('approve')"
                                    class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                                    Setujui
                                </button>
                                <button type="button" 
                                    @click="bulkActions.executeBulkAction('delete')"
                                    class="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-red-700">
                                    Hapus
                                </button>
                                <button type="button" 
                                    @click="bulkActions.clearSelection()"
                                    class="inline-flex items-center rounded-md bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-300">
                                    Batal
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase w-12">
                                            <input type="checkbox" 
                                                @change="bulkActions.toggleSelectAll()"
                                                :checked="bulkActions.selectAll"
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Nomor</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Customer Service</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Total</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Terbayar</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Sisa</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @forelse ($needsConfirmationInvoices as $invoice)
                                        <tr>
                                            <td class="px-6 py-4">
                                                <input type="checkbox" 
                                                    name="selected[]" 
                                                    value="{{ $invoice->id }}"
                                                    @change="bulkActions.updateSelectedItems()"
                                                    @click.stop
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </td>
                                            <td class="px-6 py-4">
                                                <p class="font-semibold text-gray-900">{{ $invoice->number }}</p>
                                                <p class="text-xs text-gray-500">Status: {{ ucwords($invoice->status) }}</p>
                                            </td>
                                            <td class="px-6 py-4">{{ $invoice->customer_service_name ?? $invoice->customerService?->name ?? '-' }}</td>
                                            <td class="px-6 py-4 text-right">Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}</td>
                                            <td class="px-6 py-4 text-right">Rp {{ number_format((float) $invoice->down_payment, 0, ',', '.') }}</td>
                                            <td class="px-6 py-4 text-right">Rp {{ number_format(max((float) $invoice->total - (float) $invoice->down_payment, 0), 0, ',', '.') }}</td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex flex-col items-center gap-2 sm:flex-row sm:justify-center">
                                                    @if ($invoice->hasPaymentProof())
                                                        <button type="button"
                                                            class="inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-200"
                                                            @click.stop="showProof('{{ route('invoices.payment-proof.show', $invoice) }}')">
                                                            Bukti
                                                        </button>
                                                    @endif
                                                    <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="text-sm font-medium text-gray-600 hover:text-gray-900">PDF</a>
                                                    <a href="{{ route('invoices.public.show', $invoice->public_token) }}" target="_blank" class="text-sm font-medium text-blue-600 hover:text-blue-900">Link Publik</a>
                                                    @php
                                                        $remaining = max((float) $invoice->total - (float) $invoice->down_payment, 0);
                                                        $isFullyPaid = $invoice->status === 'lunas' || $remaining <= 0;
                                                    @endphp
                                                    @if($isFullyPaid)
                                                        <form method="POST" action="{{ route('invoices.pay', $invoice) }}" class="inline">
                                                            @csrf
                                                            <button type="submit"
                                                                onclick="return confirm('Konfirmasi pembayaran untuk invoice ini?')"
                                                                class="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-green-700">
                                                                Konfirmasi (Lunas)
                                                            </button>
                                                        </form>
                                                    @else
                                                        <button type="button"
                                                            @click.stop="open({{ $invoice->id }}, {{ (float) $invoice->total }}, {{ (float) $invoice->down_payment }})"
                                                            class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                                                            Konfirmasi Pembayaran
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-6 text-center text-sm text-gray-500">Tidak ada invoice yang perlu dikonfirmasi saat ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Bulk Action Form -->
                        <form id="bulk-action-form" action="{{ route('invoices.bulk-action') }}" method="POST" style="display: none;">
                            @csrf
                            <input type="hidden" name="action" value="">
                        </form>
                    </div>
                    @endif

                    @if ($tabStates['settlement']['unlocked'] && $defaultTab === 'settlement')
                    <div x-cloak>
                        <div class="space-y-4">
                            @forelse ($settlementInvoices as $invoice)
                                <div class="rounded-lg border border-gray-200 p-4 shadow-sm">
                                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">Invoice #{{ $invoice->number }}</p>
                                            <p class="text-sm text-gray-600">Klien: {{ $invoice->client_name ?? '-' }}</p>
                                            <p class="text-sm text-gray-600">Sisa tagihan: Rp {{ number_format(max((float) $invoice->total - (float) $invoice->down_payment, 0), 0, ',', '.') }}</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800 uppercase">{{ ucwords($invoice->status) }}</span>
                                            @if ($invoice->hasPaymentProof())
                                                <button type="button"
                                                    class="inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-200"
                                                    @click="showProof('{{ route('invoices.payment-proof.show', $invoice) }}')">
                                                    Lihat Bukti
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-sm text-gray-500">Belum ada invoice yang menunggu proses pelunasan khusus.</p>
                            @endforelse
                        </div>
                    </div>
                    @endif

                    @if ($tabStates['history']['unlocked'] && $defaultTab === 'history')
                    <div x-cloak>
                        <div class="mb-6 flex flex-wrap items-center gap-4">
                            <form action="{{ route('invoices.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
                                <input type="hidden" name="tab" value="history">
                                <div>
                                    <label for="filter_date_history" class="text-sm font-medium text-gray-600">Tanggal:</label>
                                    <input type="date" name="filter_date" id="filter_date_history" value="{{ $filters['filter_date'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-600">Tipe:</span>
                                    <div class="mt-1 flex items-center gap-2">
                                        <a href="{{ route('invoices.index', ['tab' => 'history', 'type' => 'dp'] + Arr::except($filters, 'type')) }}" class="px-3 py-1 text-sm rounded-md {{ ($filters['type'] ?? '') === 'dp' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}">Down Payment</a>
                                        <a href="{{ route('invoices.index', ['tab' => 'history', 'type' => 'lunas'] + Arr::except($filters, 'type')) }}" class="px-3 py-1 text-sm rounded-md {{ ($filters['type'] ?? '') === 'lunas' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}">Bayar Lunas</a>
                                    </div>
                                </div>
                                <button type="submit" class="rounded-md bg-blue-600 p-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M1 2h14v2L9 9v7l-2-2V9L1 4V2zm0-2h14v1H1V0z"/></svg>
                                </button>
                                <a href="{{ route('invoices.index', ['tab' => 'history']) }}" class="rounded-md bg-red-500 p-2 text-sm text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><g><path d="M8 11a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2H8Z"/><path fill-rule="evenodd" d="M23 12c0 6.075-4.925 11-11 11S1 18.075 1 12S5.925 1 12 1s11 4.925 11 11Zm-2 0a9 9 0 1 1-18 0a9 9 0 0 1 18 0Z" clip-rule="evenodd"/></g></svg>
                                </a>
                            </form>
                        </div>

                        <!-- Bulk Actions Bar for History -->
                        <div x-show="bulkActionsHistory.hasSelection()" x-cloak class="mb-4 flex flex-wrap items-center gap-3 rounded-md bg-blue-50 border border-blue-200 p-3">
                            <span class="text-sm font-medium text-blue-900">
                                <span x-text="bulkActionsHistory.getSelectedCount()"></span> invoice dipilih
                            </span>
                            <div class="flex items-center gap-2">
                                <button type="button" 
                                    @click="bulkActionsHistory.executeBulkAction('send')"
                                    class="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-green-700">
                                    Kirim
                                </button>
                                <button type="button" 
                                    @click="bulkActionsHistory.executeBulkAction('delete')"
                                    class="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-red-700">
                                    Hapus
                                </button>
                                <button type="button" 
                                    @click="bulkActionsHistory.clearSelection()"
                                    class="inline-flex items-center rounded-md bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-300">
                                    Batal
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase w-12">
                                            <input type="checkbox" 
                                                @change="bulkActionsHistory.toggleSelectAll()"
                                                :checked="bulkActionsHistory.selectAll"
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Nomor</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Klien</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Total</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">Status</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @forelse ($historyInvoices as $invoice)
                                        <tr>
                                            <td class="px-6 py-4">
                                                <input type="checkbox" 
                                                    name="selected_history[]" 
                                                    value="{{ $invoice->id }}"
                                                    @change="bulkActionsHistory.updateSelectedItems()"
                                                    @click.stop
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <p class="font-semibold text-gray-900">{{ $invoice->number }}</p>
                                                <p class="text-xs text-gray-500">{{ $invoice->customer_service_name ?? $invoice->customerService?->name ?? '-' }}</p>
                                            </td>
                                            <td class="px-6 py-4">{{ $invoice->client_name ?? '-' }}</td>
                                            <td class="px-6 py-4">{{ $invoice->issue_date->format('d M Y') }}</td>
                                            <td class="px-6 py-4 text-right">Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}</td>
                                            <td class="px-6 py-4 text-center">
                                                @if ($invoice->needs_confirmation)
                                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800 uppercase">Perlu Konfirmasi</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium uppercase {{ match($invoice->status) {
                                                        'lunas' => 'bg-green-100 text-green-800',
                                                        'belum lunas' => 'bg-blue-100 text-blue-800',
                                                        'belum bayar' => 'bg-red-100 text-red-800',
                                                        default => 'bg-gray-100 text-gray-800'
                                                    } }}">{{ ucwords($invoice->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div
                                                    class="relative inline-block text-left"
                                                    x-data="{
                                                        menuOpen: false,
                                                        menuStyle: '',
                                                        dropUp: false,
                                                        toggleMenu() {
                                                            this.menuOpen = !this.menuOpen;
                                                        },
                                                        closeMenu() {
                                                            this.menuOpen = false;
                                                        },
                                                        handleWindowClick(event) {
                                                            if (!this.menuOpen) return;
                                                            const trigger = this.$refs.menuTrigger;
                                                            const menu = this.$refs.menuDropdown;
                                                            if ((trigger && trigger.contains(event.target)) || (menu && menu.contains(event.target))) {
                                                                return;
                                                            }
                                                            this.closeMenu();
                                                        },
                                                        updatePosition() {
                                                            const trigger = this.$refs.menuTrigger;
                                                            const menu = this.$refs.menuDropdown;
                                                            if (!trigger || !menu) {
                                                                return;
                                                            }

                                                            const rect = trigger.getBoundingClientRect();
                                                            const menuWidth = menu.offsetWidth;
                                                            const menuHeight = menu.offsetHeight;
                                                            const spacing = 8;
                                                            const viewportLeft = window.scrollX + 16;
                                                            const viewportRight = window.scrollX + window.innerWidth - 16;
                                                            const viewportTop = window.scrollY + 16;
                                                            const viewportBottom = window.scrollY + window.innerHeight - 16;

                                                            let left = rect.right + window.scrollX - menuWidth;
                                                            if (left < viewportLeft) {
                                                                left = viewportLeft;
                                                            }

                                                            if (left + menuWidth > viewportRight) {
                                                                left = Math.max(viewportLeft, viewportRight - menuWidth);
                                                            }

                                                            const spaceBelow = viewportBottom - rect.bottom - spacing;
                                                            const spaceAbove = rect.top - viewportTop - spacing;

                                                            this.dropUp = spaceBelow < menuHeight && spaceAbove > spaceBelow;

                                                            let top;
                                                            if (this.dropUp) {
                                                                top = rect.top + window.scrollY - spacing - menuHeight;

                                                                if (top < viewportTop) {
                                                                    this.dropUp = false;
                                                                    top = rect.bottom + window.scrollY + spacing;
                                                                }
                                                            }

                                                            if (!this.dropUp) {
                                                                top = rect.bottom + window.scrollY + spacing;

                                                                if (top + menuHeight > viewportBottom) {
                                                                    top = Math.max(viewportTop, viewportBottom - menuHeight);
                                                                }
                                                            }

                                                            this.menuStyle = `top: ${top}px; left: ${left}px;`;
                                                        }
                                                    }"
                                                    x-init="
                                                        $watch('menuOpen', value => {
                                                            if (value) {
                                                                $nextTick(() => updatePosition());
                                                            }
                                                        });
                                                    "
                                                    @keydown.escape.window.stop="closeMenu()"
                                                    @resize.window="menuOpen && updatePosition()"
                                                    @scroll.window="menuOpen && updatePosition()"
                                                    @click.window="handleWindowClick($event)"
                                                >
                                                    <button
                                                        type="button"
                                                        x-ref="menuTrigger"
                                                        @click="toggleMenu()"
                                                        class="inline-flex w-full justify-center rounded-full border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                    >
                                                        <span class="sr-only">Buka menu aksi</span>
                                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.75a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                                        </svg>
                                                    </button>

                                                    <template x-teleport="body">
                                                        <div
                                                            x-cloak
                                                            x-show="menuOpen"
                                                            x-transition.origin.top.right
                                                            x-ref="menuDropdown"
                                                            :style="menuStyle"
                                                            :class="dropUp ? 'origin-bottom-right' : 'origin-top-right'"
                                                            class="fixed z-[9999] w-52 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                                        >
                                                            <div class="py-1 text-left text-sm text-gray-700">
                                                                @if ($invoice->hasPaymentProof())
                                                                    <button type="button" @click="menuOpen = false; showProof('{{ route('invoices.payment-proof.show', $invoice) }}')" class="block w-full px-4 py-2 text-left hover:bg-gray-100">
                                                                        Lihat Bukti
                                                                    </button>
                                                                @endif
                                                                
                                                                <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="block px-4 py-2 hover:bg-gray-100" @click="menuOpen = false">
                                                                    Buka PDF
                                                                </a>
                                                                
                                                                <a href="{{ route('invoices.public.show', $invoice->public_token) }}" target="_blank" class="block px-4 py-2 hover:bg-gray-100" @click="menuOpen = false">
                                                                    Buka Link Publik
                                                                </a>
                                                                
                                                                @if ($invoice->needs_confirmation || $invoice->status !== 'lunas')
                                                                    <button type="button" @click="menuOpen = false; open({{ $invoice->id }}, {{ (float) $invoice->total }}, {{ (float) $invoice->down_payment }})" class="block w-full px-4 py-2 text-left hover:bg-gray-100">
                                                                        Catat Pembayaran
                                                                    </button>
                                                                @endif
                                                                
                                                                @can('delete', $invoice)
                                                                    <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus invoice #{{ $invoice->number }}? Tindakan ini tidak dapat dibatalkan.');">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="block w-full px-4 py-2 text-left text-red-600 hover:bg-gray-100" @click="menuOpen = false">
                                                                            Hapus
                                                                        </button>
                                                                    </form>
                                                                @endcan
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-6 text-center text-sm text-gray-500">Tidak ada invoice yang cocok dengan filter yang dipilih.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Bulk Action Form for History -->
                        <form id="bulk-action-form-history" action="{{ route('invoices.bulk-action') }}" method="POST" style="display: none;">
                            @csrf
                            <input type="hidden" name="action" value="">
                            <input type="hidden" name="tab" value="history">
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div x-show="openModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="openModal" @click.away="close()" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="openModal" @click.stop class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form :action="'/invoices/' + selectedInvoice + '/pay'" method="POST">
                        @csrf
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Catat Pembayaran
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">Sisa tagihan saat ini: <span class="font-semibold" x-text="formatCurrency(remaining)"></span></p>
                            <div class="mt-4">
                                <label for="payment_amount" class="block text-sm font-medium text-gray-700">Nominal Pembayaran</label>
                                <input type="number" name="payment_amount" id="payment_amount" step="0.01" min="0.01" :max="remaining"
                                    x-model="paymentAmount"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            <div class="mt-4">
                                <label for="payment_date" class="block text-sm font-medium text-gray-700">Tanggal Pembayaran</label>
                                <input type="date" name="payment_date" id="payment_date"
                                    x-model="paymentDate"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Simpan
                            </button>
                            <button @click="close()" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        function invoicePayments(config) {
            return {
                openModal: false,
                selectedInvoice: null,
                defaultDate: config.defaultDate,
                total: 0,
                paid: 0,
                plannedDownPayment: 0,
                paymentAmount: '',
                paymentDate: config.defaultDate,
                lightboxOpen: false,
                lightboxImageUrl: null,
                bulkActions: {
                    selectedItems: [],
                    selectAll: false,
                    updateSelectedItems() {
                        const checkboxes = Array.from(document.querySelectorAll('input[type=\'checkbox\'][name=\'selected[]\']:checked'));
                        this.selectedItems = checkboxes.map(cb => cb.value);
                        const total = document.querySelectorAll('input[type=\'checkbox\'][name=\'selected[]\']').length;
                        this.selectAll = total > 0 && this.selectedItems.length === total;
                    },
                    toggleSelectAll() {
                        const checkboxes = document.querySelectorAll('input[type=\'checkbox\'][name=\'selected[]\']');
                        this.selectAll = !this.selectAll;
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = this.selectAll;
                        });
                        this.updateSelectedItems();
                    },
                    hasSelection() {
                        return this.selectedItems.length > 0;
                    },
                    getSelectedCount() {
                        return this.selectedItems.length;
                    },
                    clearSelection() {
                        this.selectedItems = [];
                        this.selectAll = false;
                        document.querySelectorAll('input[type=\'checkbox\'][name=\'selected[]\']').forEach(cb => cb.checked = false);
                    },
                    executeBulkAction(actionValue) {
                        if (!this.hasSelection()) return;
                        
                        let confirmMsg = '';
                        if (actionValue === 'delete') {
                            confirmMsg = 'Apakah Anda yakin ingin menghapus ' + this.getSelectedCount() + ' invoice yang dipilih?';
                        } else if (actionValue === 'approve') {
                            confirmMsg = 'Setujui ' + this.getSelectedCount() + ' invoice yang dipilih? Invoice akan tidak lagi memerlukan konfirmasi.';
                        }
                        
                        if (confirmMsg && !confirm(confirmMsg)) return;
                        
                        const form = document.getElementById('bulk-action-form');
                        if (form) {
                            form.querySelector('input[name=\'action\']').value = actionValue;
                            
                            // Clear existing selected inputs
                            form.querySelectorAll('input[name=\'selected[]\']').forEach(input => input.remove());
                            
                            // Add selected items
                            this.selectedItems.forEach(item => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'selected[]';
                                input.value = item;
                                form.appendChild(input);
                            });
                            
                            form.submit();
                        }
                    }
                },
                bulkActionsHistory: {
                    selectedItems: [],
                    selectAll: false,
                    updateSelectedItems() {
                        const checkboxes = Array.from(document.querySelectorAll('input[type=\'checkbox\'][name=\'selected_history[]\']:checked'));
                        this.selectedItems = checkboxes.map(cb => cb.value);
                        const total = document.querySelectorAll('input[type=\'checkbox\'][name=\'selected_history[]\']').length;
                        this.selectAll = total > 0 && this.selectedItems.length === total;
                    },
                    toggleSelectAll() {
                        const checkboxes = document.querySelectorAll('input[type=\'checkbox\'][name=\'selected_history[]\']');
                        this.selectAll = !this.selectAll;
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = this.selectAll;
                        });
                        this.updateSelectedItems();
                    },
                    hasSelection() {
                        return this.selectedItems.length > 0;
                    },
                    getSelectedCount() {
                        return this.selectedItems.length;
                    },
                    clearSelection() {
                        this.selectedItems = [];
                        this.selectAll = false;
                        document.querySelectorAll('input[type=\'checkbox\'][name=\'selected_history[]\']').forEach(cb => cb.checked = false);
                    },
                    executeBulkAction(actionValue) {
                        if (!this.hasSelection()) return;
                        
                        let confirmMsg = '';
                        if (actionValue === 'delete') {
                            confirmMsg = 'Apakah Anda yakin ingin menghapus ' + this.getSelectedCount() + ' invoice yang dipilih?';
                        } else if (actionValue === 'send') {
                            confirmMsg = 'Kirim ' + this.getSelectedCount() + ' invoice yang dipilih?';
                        }
                        
                        if (confirmMsg && !confirm(confirmMsg)) return;
                        
                        const form = document.getElementById('bulk-action-form-history');
                        if (form) {
                            form.querySelector('input[name=\'action\']').value = actionValue;
                            
                            // Clear existing selected inputs
                            form.querySelectorAll('input[name=\'selected[]\']').forEach(input => input.remove());
                            
                            // Add selected items
                            this.selectedItems.forEach(item => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'selected[]';
                                input.value = item;
                                form.appendChild(input);
                            });
                            
                            form.submit();
                        }
                    }
                },
                init() {
                    this.$watch('lightboxOpen', (value) => {
                        document.body.classList.toggle('overflow-hidden', value);
                    });
                },
                get remaining() {
                    const remaining = this.total - this.paid;
                    return remaining > 0 ? Number(remaining.toFixed(2)) : 0;
                },
                open(id, total, downPayment, plannedDownPayment = 0) {
                    this.selectedInvoice = id;
                    this.total = Number(total || 0);
                    this.paid = Number(downPayment || 0);
                    this.plannedDownPayment = Number(plannedDownPayment || 0);
                    const remaining = this.remaining;
                    if (remaining > 0) {
                        const suggested = this.plannedDownPayment > 0
                            ? Math.min(remaining, this.plannedDownPayment)
                            : remaining;
                        this.paymentAmount = suggested > 0 ? Number(suggested.toFixed(2)) : '';
                    } else {
                        this.paymentAmount = '';
                    }
                    this.paymentDate = this.defaultDate;
                    this.openModal = true;
                },
                close() {
                    this.openModal = false;
                },
                showProof(url) {
                    if (!url) {
                        return;
                    }

                    this.lightboxImageUrl = url;
                    this.lightboxOpen = true;
                },
                closeLightbox() {
                    this.lightboxOpen = false;
                    this.lightboxImageUrl = null;
                },
                formatCurrency(value) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value || 0);
                },
            };
        }
    </script>
</x-app-layout>