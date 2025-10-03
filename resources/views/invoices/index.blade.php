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
    <div class="py-12" x-data="invoicePayments({ categories: @js($categoryOptions), defaultDate: '{{ now()->toDateString() }}' })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-4 flex flex-wrap gap-2">
                    <a href="{{ route('invoices.create') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Buat Invoices</a>
                    <a href="{{ route('customer-services.create') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Tambah Customer Service</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="border-b">
                            <tr>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Nomor</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Customer Service</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Total</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Uang Muka</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Sisa Pembayaran</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($invoices as $invoice)
                            <tr>
                                <td class="px-6 py-4">{{ $invoice->number }}</td>
                                <td class="px-6 py-4">{{ ucwords($invoice->status) }}</td>
                                <td class="px-6 py-4">{{ $invoice->customer_service_name ?? $invoice->customerService?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-right">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">Rp {{ number_format($invoice->down_payment, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">Rp {{ number_format(max($invoice->total - $invoice->down_payment, 0), 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center items-center gap-2">
                                        <a href="{{ route('invoices.pdf', $invoice) }}" class="text-gray-600 hover:text-gray-900 dark:text-white" target="_blank">PDF</a>
                                        @if($invoice->status !== 'lunas')
                                        <button type="button"
                                            @click="open({{ $invoice->id }}, {{ (float) $invoice->total }}, {{ (float) $invoice->down_payment }}, {{ (float) ($invoice->down_payment_due ?? 0) }})"
                                            class="text-green-600 hover:text-green-900">Catat Pembayaran</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $invoices->links() }}
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
                total: 0,
                paid: 0,
                plannedDownPayment: 0,
                paymentAmount: '',
                paymentDate: config.defaultDate,
                categoryId: '',
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
