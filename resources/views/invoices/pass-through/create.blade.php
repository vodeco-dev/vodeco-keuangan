<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Buat Invoice Pass Through') }}
        </h2>
    </x-slot>

    @php
        $defaultCustomerType = old('customer_type', \App\Support\PassThroughPackage::CUSTOMER_TYPE_NEW);
        $defaultDailyBalance = old('daily_balance');
        $defaultEstimatedDuration = old('estimated_duration', 1);
        $defaultMaintenanceFee = old('maintenance_fee');
        $defaultAccountCreationFee = old('account_creation_fee');
    @endphp

    <div class="py-12" x-data="passThroughInvoiceForm({
        customerType: '{{ $defaultCustomerType }}',
        dailyBalance: '{{ $defaultDailyBalance }}',
        estimatedDuration: {{ (int) $defaultEstimatedDuration }},
        maintenanceFee: '{{ $defaultMaintenanceFee }}',
        accountCreationFee: '{{ $defaultAccountCreationFee }}'
    })">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm rounded-2xl border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-6 border-b border-slate-200 dark:border-slate-700">
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Buat Faktur Pass Through</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Isi detail di bawah ini untuk membuat faktur pass through baru.</p>
                </div>
                <form action="{{ route('pass-through.invoices.store') }}" method="POST" class="p-6 space-y-8">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Informasi Pass Through</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Lengkapi data pelanggan dan komponen biaya untuk menghitung invoice secara otomatis.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Jenis Pelanggan</label>
                                <select
                                    name="customer_type"
                                    x-model="customerType"
                                    class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="new" @selected($defaultCustomerType === 'new')>Pelanggan Baru</option>
                                    <option value="existing" @selected($defaultCustomerType === 'existing')>Pelanggan Lama</option>
                                </select>
                                @error('customer_type')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Saldo Harian</label>
                                <input type="hidden" name="daily_balance" :value="form.daily_balance">
                                <input
                                    type="text"
                                    x-model="form.daily_balance_display"
                                    @input="updateCurrencyField('daily_balance', $event.target.value)"
                                    class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Contoh: 30.000"
                                >
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Nominal akan otomatis diformat menjadi ribuan. Contoh: 1000 menjadi 1.000.</p>
                                @error('daily_balance')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Estimasi Waktu (Hari)</label>
                                <input
                                    type="number"
                                    name="estimated_duration"
                                    min="1"
                                    x-model.number="form.estimated_duration"
                                    class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Contoh: 30"
                                >
                                @error('estimated_duration')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Jasa Maintenance</label>
                                <input type="hidden" name="maintenance_fee" :value="form.maintenance_fee">
                                <input
                                    type="text"
                                    x-model="form.maintenance_fee_display"
                                    @input="updateCurrencyField('maintenance_fee', $event.target.value)"
                                    class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Contoh: 10.000"
                                >
                                @error('maintenance_fee')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div x-show="customerType === 'new'" x-cloak>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Biaya Pembuatan Akun</label>
                                <input type="hidden" name="account_creation_fee" :value="customerType === 'new' ? form.account_creation_fee : 0">
                                <input
                                    type="text"
                                    x-model="form.account_creation_fee_display"
                                    @input="updateCurrencyField('account_creation_fee', $event.target.value)"
                                    :disabled="customerType !== 'new'"
                                    class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-100 disabled:text-slate-500"
                                    placeholder="Contoh: 20.000"
                                >
                                @error('account_creation_fee')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 p-5">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Ringkasan Faktur</h3>
                            <div class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                                <div class="flex items-center justify-between">
                                    <p>Jumlah Pass Through</p>
                                    <p class="font-semibold text-slate-900 dark:text-white" x-text="formatCurrency(passThroughAmount())"></p>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Saldo Harian × Estimasi Waktu</p>

                                <div class="flex items-center justify-between">
                                    <p>Biaya Jasa</p>
                                    <p class="font-semibold text-slate-900 dark:text-white" x-text="formatCurrency(maintenanceFeeValue() + accountCreationFeeValue())"></p>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Jasa Maintenance + Biaya Pembuatan Akun</p>

                                <div class="flex items-center justify-between pt-3 mt-3 border-t border-slate-200 dark:border-slate-700">
                                    <p class="text-base font-semibold text-slate-900 dark:text-white">Jumlah Total</p>
                                    <p class="text-base font-semibold text-blue-600" x-text="formatCurrency(totalAmount())"></p>
                                </div>
                            </div>
                        </div>

                        <div class="text-xs space-y-2 text-slate-500 dark:text-slate-400">
                            <p><span class="font-medium text-slate-700 dark:text-slate-200">Catatan Pass Through:</span> Dana hasil perkalian Saldo Harian dengan Estimasi Waktu akan tercatat sebagai Catatan Pass Through.</p>
                            <p><span class="font-medium text-slate-700 dark:text-slate-200">Transaksi:</span> Biaya Maintenance dan Biaya Pembuatan Akun (khusus pelanggan baru) dicatat sebagai transaksi dengan kategori yang sesuai.</p>
                            <p><span class="font-medium text-slate-700 dark:text-slate-200">Pembayaran Harian:</span> Nilai saldo harian menjadi batas maksimal pembayaran harian di menu hutang. Jika saldo harian Rp30.000 maka pembayaran Pass Through per hari tidak bisa kurang dari nominal tersebut.</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Informasi Klien</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Gunakan data klien sebagai referensi invoice dan pencatatan hutang.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Nama Klien</label>
                                <input type="text" name="client_name" value="{{ old('client_name') }}" class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500" required>
                                @error('client_name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Nomor WhatsApp Klien</label>
                                <input type="text" name="client_whatsapp" value="{{ old('client_whatsapp') }}" class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500" required>
                                @error('client_whatsapp')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Alamat Klien</label>
                                <textarea name="client_address" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500">{{ old('client_address') }}</textarea>
                                @error('client_address')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Tanggal Jatuh Tempo</label>
                                <input type="date" name="due_date" value="{{ old('due_date') }}" class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500">
                                @error('due_date')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-end">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Periksa kembali angka sebelum menyimpan invoice.</span>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900">
                            Simpan Invoice Pass Through
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
    function passThroughInvoiceForm(config) {
        return {
            customerType: config.customerType || 'new',
            form: {
                daily_balance: config.dailyBalance ? String(config.dailyBalance).replace(/\D/g, '') : '',
                daily_balance_display: '',
                estimated_duration: Number.isFinite(Number(config.estimatedDuration)) && Number(config.estimatedDuration) > 0
                    ? Number(config.estimatedDuration)
                    : 1,
                maintenance_fee: config.maintenanceFee ? String(config.maintenanceFee).replace(/\D/g, '') : '',
                maintenance_fee_display: '',
                account_creation_fee: config.accountCreationFee ? String(config.accountCreationFee).replace(/\D/g, '') : '',
                account_creation_fee_display: '',
            },
            init() {
                this.setCurrencyField('daily_balance', this.form.daily_balance);
                this.setCurrencyField('maintenance_fee', this.form.maintenance_fee);
                this.setCurrencyField('account_creation_fee', this.form.account_creation_fee);

                this.$watch('customerType', (value) => {
                    if (value !== 'new') {
                        this.setCurrencyField('account_creation_fee', '');
                    }
                });
            },
            updateCurrencyField(field, value) {
                const digits = String(value || '').replace(/\D/g, '');
                this.form[field] = digits;
                this.form[`${field}_display`] = digits ? this.formatNumber(digits) : '';
            },
            setCurrencyField(field, value) {
                const digits = String(value || '').replace(/\D/g, '');
                this.form[field] = digits;
                this.form[`${field}_display`] = digits ? this.formatNumber(digits) : '';
            },
            formatNumber(value) {
                return String(value).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            },
            formatCurrency(value) {
                const numeric = Number(value) || 0;
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0,
                }).format(numeric);
            },
            passThroughAmount() {
                const daily = Number(this.form.daily_balance) || 0;
                const duration = Number(this.form.estimated_duration) || 0;
                return daily * duration;
            },
            maintenanceFeeValue() {
                return Number(this.form.maintenance_fee) || 0;
            },
            accountCreationFeeValue() {
                if (this.customerType !== 'new') {
                    return 0;
                }

                return Number(this.form.account_creation_fee) || 0;
            },
            totalAmount() {
                return this.passThroughAmount() + this.maintenanceFeeValue() + this.accountCreationFeeValue();
            },
        };
    }
</script>
@endpush
