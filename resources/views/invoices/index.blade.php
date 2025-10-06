<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Invoices') }}
        </h2>
    </x-slot>

    @php
        $categoryOptions = $incomeCategories->map(fn ($category) => [
            'id' => $category->id,
            'name' => $category->name,
        ])->values();
    @endphp
    <div class="py-12" x-data="invoicePayments({ categories: @js($categoryOptions), defaultDate: '{{ now()->toDateString() }}', activeTab: @js($defaultTab) })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('access_code_status'))
                    <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                        {{ session('access_code_status') }}
                    </div>
                @endif

                @if (session('access_code_generated'))
                    @php($generated = session('access_code_generated'))
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

                @php($unlockedTabs = collect($tabStates)->filter(fn ($tab) => $tab['unlocked']))
                <div>
                    @if ($unlockedTabs->isNotEmpty())
                        <div class="border-b border-gray-200 mb-6">
                            <nav class="-mb-px flex flex-wrap gap-2" aria-label="Tabs">
                                @foreach ($tabStates as $key => $tab)
                                    @if ($tab['unlocked'])
                                        <button type="button" @click="switchTab('{{ $key }}')" :class="tabClass('{{ $key }}')">
                                            {{ $tab['label'] }}
                                        </button>
                                    @endif
                                @endforeach
                            </nav>
                        </div>
                    @else
                        <div class="mb-6 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                            Tidak ada tab yang dapat ditampilkan. Pastikan Anda memiliki kode akses yang valid atau hubungi administrator.
                        </div>
                    @endif

                    @if ($tabStates['down-payment']['unlocked'])
                    <div x-show="activeTab === 'down-payment'" x-cloak>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Nomor</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Klien</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Total</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">DP Tercatat</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Rencana DP</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @forelse ($downPaymentInvoices as $invoice)
                                        <tr>
                                            <td class="px-6 py-4">
                                                <p class="font-semibold text-gray-900">{{ $invoice->number }}</p>
                                                <p class="text-xs text-gray-500">Status: {{ ucwords($invoice->status) }}</p>
                                            </td>
                                            <td class="px-6 py-4">{{ $invoice->client_name ?? '-' }}</td>
                                            <td class="px-6 py-4 text-right">Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}</td>
                                            <td class="px-6 py-4 text-right">Rp {{ number_format((float) $invoice->down_payment, 0, ',', '.') }}</td>
                                            <td class="px-6 py-4 text-right">Rp {{ number_format((float) $invoice->down_payment_due, 0, ',', '.') }}</td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex flex-col items-center gap-2 sm:flex-row sm:justify-center">
                                                    <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="text-sm font-medium text-gray-600 hover:text-gray-900">PDF</a>
                                                    <button type="button"
                                                        @click="open({{ $invoice->id }}, {{ (float) $invoice->total }}, {{ (float) $invoice->down_payment }}, {{ (float) ($invoice->down_payment_due ?? 0) }})"
                                                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-green-700">
                                                        Konfirmasi Down Payment
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-6 text-center text-sm text-gray-500">Belum ada invoice yang membutuhkan konfirmasi down payment.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if ($tabStates['pay-in-full']['unlocked'])
                    <div x-show="activeTab === 'pay-in-full'" x-cloak>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Nomor</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Customer Service</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Total</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Terbayar</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Sisa</th>
                                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @forelse ($payInFullInvoices as $invoice)
                                        <tr>
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
                                                    <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="text-sm font-medium text-gray-600 hover:text-gray-900">PDF</a>
                                                    <button type="button"
                                                        @click="open({{ $invoice->id }}, {{ (float) $invoice->total }}, {{ (float) $invoice->down_payment }})"
                                                        class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                                                        Catat Pelunasan
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-6 text-center text-sm text-gray-500">Tidak ada invoice yang siap dilunasi saat ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if ($tabStates['settlement']['unlocked'])
                    <div x-show="activeTab === 'settlement'" x-cloak>
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
                                            <a href="{{ route('invoices.public.show', $invoice->public_token) }}" target="_blank" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                                Buka Tautan Pelunasan
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-sm text-gray-500">Belum ada invoice yang menunggu proses pelunasan khusus.</p>
                            @endforelse
                        </div>
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
                            <div class="mt-4" x-show="willBePaidOff" x-cloak>
                                <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori Pemasukan</label>
                                <select name="category_id" id="category_id" x-model="categoryId" :required="willBePaidOff" :disabled="!willBePaidOff"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Pilih kategori pemasukan</option>
                                    <template x-for="category in categories" :key="category.id">
                                        <option :value="category.id" x-text="category.name"></option>
                                    </template>
                                </select>
                                <p class="mt-2 text-xs text-gray-500">Pilih kategori pemasukan untuk mencatat pembayaran lunas.</p>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
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
                categories: config.categories ?? [],
                defaultDate: config.defaultDate,
                activeTab: config.activeTab ?? 'down-payment',
                total: 0,
                paid: 0,
                plannedDownPayment: 0,
                paymentAmount: '',
                paymentDate: config.defaultDate,
                categoryId: '',
                tabClass(tab) {
                    const isActive = this.activeTab === tab;
                    const baseClasses = 'whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium focus:outline-none';
                    return isActive
                        ? `${baseClasses} border-blue-500 text-blue-600`
                        : `${baseClasses} border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300`;
                },
                switchTab(tab) {
                    this.activeTab = tab;
                },
                get remaining() {
                    const remaining = this.total - this.paid;
                    return remaining > 0 ? Number(remaining.toFixed(2)) : 0;
                },
                get willBePaidOff() {
                    const amount = Number(this.paymentAmount || 0);
                    return this.remaining > 0 && amount >= this.remaining;
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
                    this.categoryId = '';
                    this.openModal = true;
                },
                close() {
                    this.openModal = false;
                },
                formatCurrency(value) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value || 0);
                },
            };
        }
    </script>
</x-app-layout>
