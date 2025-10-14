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
                    $passThroughPackagesCollection = ($passThroughPackages ?? collect())->map(function ($package) {
                        return [
                            'id' => $package->id,
                            'name' => $package->name,
                            'customer_type' => $package->customerType,
                            'customer_label' => $package->customerLabel(),
                            'daily_balance' => $package->dailyBalance,
                            'duration_days' => $package->durationDays,
                            'maintenance_fee' => $package->maintenanceFee,
                            'account_creation_fee' => $package->accountCreationFee,
                        ];
                    });
                    $firstPassThroughPackage = $passThroughPackagesCollection->first();
                    $defaultPassThroughPackageId = old('pass_through_package_id', $firstPassThroughPackage['id'] ?? null);
                    $defaultPassThroughQuantity = old('pass_through_quantity', 1);
                    $defaultPassThroughDescription = old('pass_through_description');
                    $passThroughSettingsUrl = route('debts.index', [
                        'type_filter' => \App\Models\Debt::TYPE_PASS_THROUGH,
                        'open_pass_through_modal' => 1,
                    ]);
                @endphp
                <form action="{{ route('invoices.store') }}" method="POST" class="p-6" id="invoice-form"
                    x-data="invoicePortalForm({
                        defaultTransaction: @json(old('transaction_type', 'down_payment')),
                        passThrough: {
                            packages: @json($passThroughPackagesCollection->values()),
                            defaults: {
                                packageId: @json($defaultPassThroughPackageId),
                                quantity: @json((int) $defaultPassThroughQuantity),
                                description: @json($defaultPassThroughDescription),
                                adBudgetTotal: @json(old('pass_through_ad_budget_total')),
                                maintenanceTotal: @json(old('pass_through_maintenance_total')),
                                accountCreationTotal: @json(old('pass_through_account_creation_total')),
                                totalPrice: @json(old('pass_through_total_price')),
                            },
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
                        <div class="space-y-6 rounded-2xl border border-gray-200 bg-gray-50 p-6">
                            <div class="flex flex-col gap-2">
                                <h3 class="text-lg font-semibold text-gray-900">Invoices Iklan</h3>
                                <p class="text-sm text-gray-600">Pilih paket yang tersedia untuk menghitung biaya secara otomatis dan lengkapi deskripsi pesanan.</p>
                            </div>

                            @if ($passThroughPackagesCollection->isEmpty())
                                <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 space-y-3">
                                    <p>Belum ada paket Invoices Iklan yang tersedia. Tambahkan paket terlebih dahulu melalui menu pengaturan agar perhitungan otomatis dapat digunakan.</p>
                                    <a
                                        href="{{ $passThroughSettingsUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
                                    >
                                        Buka Pengaturan Paket
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5v14h14" />
                                        </svg>
                                    </a>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label for="pass_through_description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                    <input
                                        type="text"
                                        name="pass_through_description"
                                        id="pass_through_description"
                                        x-model="passThroughDescription"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                                        placeholder="Contoh: Kampanye Iklan Marketplace"
                                        @if ($passThroughPackagesCollection->isEmpty()) disabled @endif
                                    >
                                    @error('pass_through_description')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="pass_through_package_id">Pilihan Paket</label>
                                    <select
                                        name="pass_through_package_id"
                                        id="pass_through_package_id"
                                        x-model="passThroughPackageId"
                                        class="mt-1 block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                                        @if ($passThroughPackagesCollection->isEmpty()) disabled @endif
                                    >
                                        <option value="" disabled>Pilih paket</option>
                                        <template x-for="pkg in passThroughPackages" :key="pkg.id">
                                            <option :value="pkg.id" x-text="formatPackageOption(pkg)"></option>
                                        </template>
                                    </select>
                                    @error('pass_through_package_id')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="pass_through_quantity">Kuantitas</label>
                                    <input
                                        type="number"
                                        min="1"
                                        id="pass_through_quantity"
                                        x-model="passThroughQuantityInput"
                                        @input="updatePassThroughQuantity($event.target.value)"
                                        @blur="normalizePassThroughQuantity()"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                                        placeholder="1"
                                        @if ($passThroughPackagesCollection->isEmpty()) disabled @endif
                                    >
                                    @error('pass_through_quantity')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700" for="pass_through_total_display">Harga Total</label>
                                    <input
                                        type="text"
                                        id="pass_through_total_display"
                                        :value="formatCurrency(passThroughTotalPrice())"
                                        class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-100 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        readonly
                                    >
                                    @error('pass_through_total_price')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <input type="hidden" name="pass_through_quantity" :value="passThroughQuantity()">
                            <input type="hidden" name="pass_through_ad_budget_total" :value="passThroughAdBudgetTotal()">
                            <input type="hidden" name="pass_through_maintenance_total" :value="passThroughMaintenanceTotal()">
                            <input type="hidden" name="pass_through_account_creation_total" :value="passThroughAccountCreationTotal()">
                            <input type="hidden" name="pass_through_total_price" :value="passThroughTotalPrice()">
                            <input type="hidden" name="pass_through_daily_balance_total" :value="passThroughDailyBalanceTotal()">
                            <input type="hidden" name="pass_through_duration_days" :value="passThroughDurationDays()">
                        </div>

                        <div class="rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm" x-show="hasPassThroughPackageSelected()" x-cloak>
                            <h4 class="text-base font-semibold text-gray-900">Ringkasan Paket</h4>
                            <dl class="mt-4 grid grid-cols-1 gap-4 text-sm text-gray-700 md:grid-cols-2">
                                <div>
                                    <dt class="font-medium text-gray-600">Nama Paket</dt>
                                    <dd class="mt-1" x-text="selectedPackage()?.name || '-'">-</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600">Jenis Pelanggan</dt>
                                    <dd class="mt-1" x-text="selectedPackage()?.customer_label || '-'">-</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600">Saldo Harian</dt>
                                    <dd class="mt-1" x-text="formatCurrency(passThroughDailyBalanceUnit())"></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600">Durasi Tayang</dt>
                                    <dd class="mt-1" x-text="passThroughDurationDays() + ' hari'"></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600">Dana Iklan / Paket</dt>
                                    <dd class="mt-1" x-text="formatCurrency(passThroughAdBudgetUnit())"></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600">Jasa Maintenance / Paket</dt>
                                    <dd class="mt-1" x-text="formatCurrency(passThroughMaintenanceUnit())"></dd>
                                </div>
                                <div x-show="showsAccountCreationFee()">
                                    <dt class="font-medium text-gray-600">Biaya Pembuatan Akun / Paket</dt>
                                    <dd class="mt-1" x-text="formatCurrency(passThroughAccountCreationUnit())"></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600">Kuantitas</dt>
                                    <dd class="mt-1" x-text="passThroughQuantity() + ' paket'"></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600">Total Dana Iklan</dt>
                                    <dd class="mt-1 text-base font-semibold text-purple-600" x-text="formatCurrency(passThroughAdBudgetTotal())"></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600">Total Maintenance</dt>
                                    <dd class="mt-1" x-text="formatCurrency(passThroughMaintenanceTotal())"></dd>
                                </div>
                                <div x-show="showsAccountCreationFee()">
                                    <dt class="font-medium text-gray-600">Total Pembuatan Akun</dt>
                                    <dd class="mt-1" x-text="formatCurrency(passThroughAccountCreationTotal())"></dd>
                                </div>
                                <div class="md:col-span-2">
                                    <dt class="font-medium text-gray-600">Total Invoice</dt>
                                    <dd class="mt-1 text-lg font-semibold text-green-600" x-text="formatCurrency(passThroughTotalPrice())"></dd>
                                </div>
                            </dl>
                            <p class="mt-4 text-xs text-gray-500">Dana Invoices Iklan dicatat sebagai hutang sebesar saldo harian dikalikan waktu tayang. Biaya maintenance dan pembuatan akun otomatis tercatat sebagai pemasukan.</p>
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
