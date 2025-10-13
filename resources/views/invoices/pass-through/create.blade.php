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
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('pass-through.invoices.store') }}" method="POST" class="p-6 space-y-8">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Informasi Pass Through</h3>
                            <p class="text-sm text-gray-500">Isi detail pelanggan dan nilai pass through sesuai kebutuhan.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Jenis Pelanggan</label>
                                <select name="customer_type" x-model="customerType" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="new">Pelanggan Baru</option>
                                    <option value="existing">Pelanggan Lama</option>
                                </select>
                                @error('customer_type')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Saldo Harian</label>
                                <input type="hidden" name="daily_balance" :value="form.daily_balance">
                                <input
                                    type="text"
                                    x-model="form.daily_balance_display"
                                    @input="updateCurrencyField('daily_balance', $event.target.value)"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    placeholder="Contoh: 30.000"
                                >
                                <p class="mt-1 text-xs text-gray-500">Nominal akan otomatis diformat menjadi ribuan. Contoh: 1000 menjadi 1.000.</p>
                                @error('daily_balance')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Estimasi Waktu (Hari)</label>
                                <input
                                    type="number"
                                    name="estimated_duration"
                                    min="1"
                                    x-model.number="form.estimated_duration"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    placeholder="Contoh: 30"
                                >
                                @error('estimated_duration')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Jasa Maintenance</label>
                                <input type="hidden" name="maintenance_fee" :value="form.maintenance_fee">
                                <input
                                    type="text"
                                    x-model="form.maintenance_fee_display"
                                    @input="updateCurrencyField('maintenance_fee', $event.target.value)"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    placeholder="Contoh: 10.000"
                                >
                                @error('maintenance_fee')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div x-show="customerType === 'new'" x-cloak>
                                <label class="block text-sm font-medium text-gray-700">Biaya Pembuatan Akun</label>
                                <input type="hidden" name="account_creation_fee" :value="customerType === 'new' ? form.account_creation_fee : 0">
                                <input
                                    type="text"
                                    x-model="form.account_creation_fee_display"
                                    @input="updateCurrencyField('account_creation_fee', $event.target.value)"
                                    :disabled="customerType !== 'new'"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm disabled:bg-gray-100 disabled:text-gray-500"
                                    placeholder="Contoh: 20.000"
                                >
                                @error('account_creation_fee')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Ringkasan Perhitungan</h3>
                        <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
                            <div>
                                <dt class="font-medium text-gray-600">Dana Pass Through</dt>
                                <dd class="mt-1 text-base font-semibold text-purple-600" x-text="formatCurrency(passThroughAmount())"></dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-600">Jasa Maintenance</dt>
                                <dd class="mt-1" x-text="formatCurrency(maintenanceFeeValue())"></dd>
                            </div>
                            <div x-show="customerType === 'new'">
                                <dt class="font-medium text-gray-600">Biaya Pembuatan Akun</dt>
                                <dd class="mt-1" x-text="formatCurrency(accountCreationFeeValue())"></dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="font-medium text-gray-600">Total Invoice</dt>
                                <dd class="mt-1 text-lg font-semibold text-green-600" x-text="formatCurrency(totalAmount())"></dd>
                            </div>
                        </dl>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Informasi Klien</h3>
                            <p class="text-sm text-gray-500">Gunakan data klien sebagai referensi invoice dan catatan hutang.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nama Klien</label>
                                <input type="text" name="client_name" value="{{ old('client_name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                @error('client_name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nomor WhatsApp Klien</label>
                                <input type="text" name="client_whatsapp" value="{{ old('client_whatsapp') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                @error('client_whatsapp')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Alamat Klien</label>
                                <textarea name="client_address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('client_address') }}</textarea>
                                @error('client_address')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal Jatuh Tempo</label>
                                <input type="date" name="due_date" value="{{ old('due_date') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @error('due_date')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-green-700">
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
