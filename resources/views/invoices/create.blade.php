<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Buat Invoice Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @php
                    $oldItems = old('items', [[
                        'description' => '',
                        'quantity' => 1,
                        'price' => '',
                        'category_id' => optional($incomeCategories->first())->id,
                    ]]);
                    $categoryOptions = $incomeCategories->map(fn ($category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ])->values();
                    $defaultPassThroughCustomerType = old('pass_through_customer_type', \App\Support\PassThroughPackage::CUSTOMER_TYPE_NEW);
                    $defaultPassThroughDailyBalance = old('pass_through_daily_balance');
                    $defaultPassThroughEstimatedDays = old('pass_through_estimated_days', 1);
                    $defaultPassThroughMaintenanceFee = old('pass_through_maintenance_fee');
                    $defaultPassThroughAccountCreationFee = old('pass_through_account_creation_fee');
                @endphp
                <form action="{{ route('invoices.store') }}" method="POST" class="p-6" id="invoice-form"
                    x-data="invoicePortalForm({
                        defaultTransaction: @json(old('transaction_type', 'down_payment')),
                        passThroughDefaults: {
                            customerType: @json($defaultPassThroughCustomerType),
                            dailyBalance: @json($defaultPassThroughDailyBalance),
                            estimatedDays: @json($defaultPassThroughEstimatedDays),
                            maintenanceFee: @json($defaultPassThroughMaintenanceFee),
                            accountCreationFee: @json($defaultPassThroughAccountCreationFee),
                        },
                    })">
                    @csrf

                    {{-- Informasi Klien --}}
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Informasi Klien</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="client_name" class="block text-sm font-medium text-gray-700">Nama Klien</label>
                            <input type="text" name="client_name" id="client_name" value="{{ old('client_name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            @error('client_name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="client_whatsapp" class="block text-sm font-medium text-gray-700">Nomor WhatsApp Klien</label>
                            <input type="text" name="client_whatsapp" id="client_whatsapp" value="{{ old('client_whatsapp') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            @error('client_whatsapp')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="client_address" class="block text-sm font-medium text-gray-700">Alamat Klien</label>
                            <textarea name="client_address" id="client_address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>{{ old('client_address') }}</textarea>
                            @error('client_address')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Informasi Invoice --}}
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Detail Invoice</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-700">Tanggal Jatuh Tempo</label>
                            <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @error('due_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Item Invoice --}}
                    <div class="mt-8">
                        <x-invoice.transaction-tabs
                            id="internal-invoice-tabs"
                            form-id="invoice-form"
                            :items="$oldItems"
                            :category-options="$categoryOptions"
                            :allowed-transactions="['down_payment', 'full_payment', 'pass_through', 'settlement']"
                            :default-transaction="old('transaction_type', 'down_payment')"
                            variant="internal"
                            down-payment-field-label="Nominal Down Payment"
                            down-payment-placeholder="Contoh: 5.000.000"
                            down-payment-help="Nominal DP wajib diisi untuk transaksi Down Payment."
                            :down-payment-required="true"
                            add-item-button-label="Tambah Item"
                            total-label="Total"
                            :down-payment-value="old('down_payment_due')"
                            :settlement-invoice-number="old('settlement_invoice_number')"
                            :settlement-remaining-balance="old('settlement_remaining_balance')"
                            :settlement-paid-amount="old('settlement_paid_amount')"
                            :settlement-payment-status="old('settlement_payment_status')"
                            data-reference-url-template="{{ route('invoices.reference', ['number' => '__NUMBER__']) }}"
                        />
                    </div>

                    <div class="mt-8 space-y-6" x-show="activeTab === 'pass_through'" x-cloak>
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6">
                            <div class="flex flex-col gap-2">
                                <h3 class="text-lg font-semibold text-gray-900">Invoices Iklan</h3>
                                <p class="text-sm text-gray-600">Isi saldo harian, estimasi hari, serta biaya tambahan untuk menghitung total Invoices Iklan secara otomatis.</p>
                            </div>

                            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Jenis Pelanggan</label>
                                    <select name="pass_through_customer_type" x-model="passThroughCustomerType"
                                        class="mt-1 block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="{{ \App\Support\PassThroughPackage::CUSTOMER_TYPE_NEW }}">Pelanggan Baru</option>
                                        <option value="{{ \App\Support\PassThroughPackage::CUSTOMER_TYPE_EXISTING }}">Pelanggan Lama</option>
                                    </select>
                                    @error('pass_through_customer_type')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Saldo Harian</label>
                                    <input type="hidden" name="pass_through_daily_balance" :value="passThroughDailyBalance">
                                    <input type="text" x-model="passThroughDailyBalanceDisplay"
                                        @input="updateCurrencyField('DailyBalance', $event.target.value)"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Contoh: 30.000">
                                    <p class="mt-1 text-xs text-gray-500">Nominal otomatis diformat menjadi ribuan.</p>
                                    @error('pass_through_daily_balance')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Estimasi Waktu (Hari)</label>
                                    <input type="number" name="pass_through_estimated_days" min="1"
                                        x-model.number="passThroughEstimatedDays"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Contoh: 30">
                                    @error('pass_through_estimated_days')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Jasa Maintenance</label>
                                    <input type="hidden" name="pass_through_maintenance_fee" :value="passThroughMaintenanceFee">
                                    <input type="text" x-model="passThroughMaintenanceFeeDisplay"
                                        @input="updateCurrencyField('MaintenanceFee', $event.target.value)"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Contoh: 10.000">
                                    @error('pass_through_maintenance_fee')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div x-show="passThroughCustomerType === '{{ \App\Support\PassThroughPackage::CUSTOMER_TYPE_NEW }}'" x-cloak>
                                    <label class="block text-sm font-medium text-gray-700">Biaya Pembuatan Akun</label>
                                    <input type="hidden" name="pass_through_account_creation_fee"
                                        :value="passThroughCustomerType === 'new' ? passThroughAccountCreationFee : 0">
                                    <input type="text" x-model="passThroughAccountCreationFeeDisplay"
                                        @input="updateCurrencyField('AccountCreationFee', $event.target.value)"
                                        :disabled="passThroughCustomerType !== 'new'"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                                        placeholder="Contoh: 20.000">
                                    @error('pass_through_account_creation_fee')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm">
                            <h4 class="text-base font-semibold text-gray-900">Ringkasan Perhitungan</h4>
                            <dl class="mt-4 grid grid-cols-1 gap-4 text-sm text-gray-700 sm:grid-cols-2">
                                <div>
                                    <dt class="font-medium text-gray-600">Dana Invoices Iklan</dt>
                                    <dd class="mt-1 text-base font-semibold text-purple-600" x-text="formatCurrency(passThroughAmount())"></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600">Jasa Maintenance</dt>
                                    <dd class="mt-1" x-text="formatCurrency(maintenanceFeeValue())"></dd>
                                </div>
                                <div x-show="passThroughCustomerType === '{{ \App\Support\PassThroughPackage::CUSTOMER_TYPE_NEW }}'">
                                    <dt class="font-medium text-gray-600">Biaya Pembuatan Akun</dt>
                                    <dd class="mt-1" x-text="formatCurrency(accountCreationFeeValue())"></dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="font-medium text-gray-600">Total Invoice</dt>
                                    <dd class="mt-1 text-lg font-semibold text-green-600" x-text="formatCurrency(totalPassThrough())"></dd>
                                </div>
                            </dl>
                            <p class="mt-4 text-xs text-gray-500">Dana Invoices Iklan dicatat sebagai hutang sebesar saldo harian dikalikan estimasi hari. Biaya lainnya otomatis masuk transaksi pemasukan.</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="px-6 py-3 text-base font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Simpan Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-app-layout>
