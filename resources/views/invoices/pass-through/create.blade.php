<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Buat Invoice Pass Through') }}
        </h2>
    </x-slot>

    @php
        $defaultCustomerType = old('customer_type', \App\Support\PassThroughPackage::CUSTOMER_TYPE_NEW);
        $defaultPackageId = old('package_id');
    @endphp

    <div class="py-12" x-data="passThroughInvoiceForm({
        packagesByType: @js($packagesByType),
        packages: @js($packagesById),
        defaultType: '{{ $defaultCustomerType }}',
        defaultPackage: '{{ $defaultPackageId }}'
    })">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('pass-through.invoices.store') }}" method="POST" class="p-6 space-y-8">
                    @csrf

                    @if (empty($packagesById))
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            Belum ada paket pass through yang tersedia. Silakan atur paket terlebih dahulu melalui menu Hutang &gt; Pengaturan Pass Through.
                        </div>
                    @endif

                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Informasi Paket</h3>
                            <p class="text-sm text-gray-500">Pilih jenis pelanggan dan paket pass through yang sesuai.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Jenis Pelanggan</label>
                                <select name="customer_type" x-model="selectedType" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="new">Pelanggan Baru</option>
                                    <option value="existing">Pelanggan Lama</option>
                                </select>
                                @error('customer_type')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Paket Pass Through</label>
                                <select name="package_id" x-model="selectedPackage" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">Pilih paket</option>
                                    <template x-for="option in packageOptions()" :key="option.id">
                                        <option :value="option.id" x-text="option.name"></option>
                                    </template>
                                </select>
                                @error('package_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
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

                    <div class="bg-gray-50 rounded-xl p-6 border border-gray-200" x-show="selectedPackageData()" x-cloak>
                        <h3 class="text-lg font-semibold text-gray-900">Ringkasan Paket</h3>
                        <p class="text-sm text-gray-500">Detail pembagian biaya dari paket yang dipilih.</p>
                        <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
                            <div>
                                <dt class="font-medium text-gray-600">Harga Paket</dt>
                                <dd class="mt-1 text-base font-semibold" x-text="formatCurrency(selectedPackageData()?.package_price || 0)"></dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-600">Saldo Harian Terpotong</dt>
                                <dd class="mt-1" x-text="formatCurrency(selectedPackageData()?.daily_deduction || 0)"></dd>
                            </div>
                            <div x-show="selectedType === 'new'">
                                <dt class="font-medium text-gray-600">Biaya Pembuatan Akun Iklan</dt>
                                <dd class="mt-1" x-text="formatCurrency(selectedPackageData()?.account_creation_fee || 0)"></dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-600">Biaya Maintenance</dt>
                                <dd class="mt-1" x-text="formatCurrency(selectedPackageData()?.maintenance_fee || 0)"></dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-600">Biaya Perpanjangan</dt>
                                <dd class="mt-1" x-text="formatCurrency(selectedPackageData()?.renewal_fee || 0)"></dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="font-medium text-gray-600">Dana Pass Through</dt>
                                <dd class="mt-1 text-lg font-semibold text-purple-600" x-text="formatCurrency(passThroughAmount())"></dd>
                            </div>
                        </dl>
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
    function passThroughInvoiceForm({ packagesByType, packages, defaultType, defaultPackage }) {
        return {
            packagesByType,
            packages,
            selectedType: defaultType || 'new',
            selectedPackage: defaultPackage || '',
            packageOptions() {
                const options = this.packagesByType[this.selectedType] || [];
                if (options.length && !options.find(option => option.id === this.selectedPackage)) {
                    this.selectedPackage = options[0].id;
                }
                return options;
            },
            selectedPackageData() {
                return this.packages[this.selectedPackage] || null;
            },
            passThroughAmount() {
                const data = this.selectedPackageData();
                if (!data) {
                    return 0;
                }

                let deductions = Number(data.maintenance_fee || 0) + Number(data.renewal_fee || 0);

                if (this.selectedType === 'new') {
                    deductions += Number(data.account_creation_fee || 0);
                }

                const total = Number(data.package_price || 0) - deductions;
                return total > 0 ? total : 0;
            },
            formatCurrency(value) {
                const number = Number(value || 0);
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
            }
        };
    }
</script>
@endpush
