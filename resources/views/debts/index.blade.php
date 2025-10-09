@extends('layouts.app')

@section('content')
@php
    $initialType = old('type', \App\Models\Debt::TYPE_DOWN_PAYMENT);
    $initialCategoryId = old('category_id');
    if ($initialCategoryId === null) {
        $initialCategoryId = $initialType === \App\Models\Debt::TYPE_DOWN_PAYMENT
            ? ($defaultIncomeCategoryId ?? null)
            : ($defaultExpenseCategoryId ?? null);
    }

    $oldAmount = old('amount');
    $rawOldAmount = $oldAmount !== null ? preg_replace('/\D/', '', (string) $oldAmount) : '';
    $formattedOldAmount = $rawOldAmount !== '' ? number_format((int) $rawOldAmount, 0, ',', '.') : '';
    $shouldOpenPassThroughModal = session('open_pass_through_modal')
        || $errors->hasBag('passThroughPackage')
        || $errors->hasBag('passThroughPackageUpdate');
@endphp
{{-- Tambahkan 'detailModal' ke dalam x-data untuk mengontrol modal baru --}}
<div x-data="{
        addModal: false,
        paymentModal: false,
        detailModal: false,
        categoryModal: false,
        passThroughModal: {{ $shouldOpenPassThroughModal ? 'true' : 'false' }},
        selectedDebt: {},
        paymentCategoryId: null,
        selectableIncomeCategories: @js($selectableIncomeCategories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])->values()),
        selectableExpenseCategories: @js($selectableExpenseCategories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])->values()),
        selectedType: '{{ $initialType }}',
        selectedCategoryId: '{{ $initialCategoryId ?? '' }}',
        defaultIncomeCategoryId: '{{ $defaultIncomeCategoryId ?? '' }}',
        defaultExpenseCategoryId: '{{ $defaultExpenseCategoryId ?? '' }}',
        formattedAmount: @js($formattedOldAmount),
        rawAmount: @js($rawOldAmount),
        activeTab: @js(request('type_filter') === \App\Models\Debt::TYPE_PASS_THROUGH
            ? \App\Models\Debt::TYPE_PASS_THROUGH
            : \App\Models\Debt::TYPE_DOWN_PAYMENT),
        openPaymentModal(debt) {
            this.selectedDebt = debt;
            const categories = debt.type === '{{ \App\Models\Debt::TYPE_DOWN_PAYMENT }}'
                ? this.selectableIncomeCategories
                : this.selectableExpenseCategories;

            if (debt.category_id) {
                this.paymentCategoryId = debt.category_id;
            } else if (categories.length > 0) {
                this.paymentCategoryId = categories[0].id;
            } else {
                this.paymentCategoryId = null;
            }

            this.paymentModal = true;
        },
        addFormCategories() {
            return this.selectedType === '{{ \App\Models\Debt::TYPE_DOWN_PAYMENT }}'
                ? this.selectableIncomeCategories
                : this.selectableExpenseCategories;
        },
        ensureAddFormCategory() {
            const categories = this.addFormCategories();
            if (!categories.some(category => String(category.id) === String(this.selectedCategoryId))) {
                this.selectedCategoryId = categories.length ? categories[0].id : '';
            }
        },
        formatCurrencyInput(value) {
            const digits = (value || '').replace(/\D/g, '');
            this.rawAmount = digits;
            this.formattedAmount = digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        },
        paymentCategories() {
            if (!this.selectedDebt.type) {
                return [];
            }

            return this.selectedDebt.type === '{{ \App\Models\Debt::TYPE_DOWN_PAYMENT }}'
                ? this.selectableIncomeCategories
                : this.selectableExpenseCategories;
        },
        init() {
            this.ensureAddFormCategory();
            if (this.rawAmount || this.formattedAmount) {
                this.formatCurrencyInput(this.rawAmount || this.formattedAmount);
            }
        }
    }" x-init="init()">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Manajemen Pass Through & Down Payment</h2>
        <div class="flex items-center gap-3">
            <button @click="addModal = true" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Tambah Catatan Baru</span>
            </button>
            <button @click="passThroughModal = true" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 flex items-center gap-2">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4H6a2 2 0 00-2 2v5m11-7h5a2 2 0 012 2v5M9 20h6m-3-3v3m-7-8h16" />
                </svg>
                <span>Pengaturan Pass Through</span>
            </button>
            <button @click="categoryModal = true" type="button" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100" title="Pengaturan pilihan kategori">
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13v-2a1 1 0 0 0-1-1h-.757l-.707-1.707.535-.536a1 1 0 0 0 0-1.414l-1.414-1.414a1 1 0 0 0-1.414 0l-.536.535L14 4.757V4a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v.757l-1.707.707-.536-.535a1 1 0 0 0-1.414 0L4.929 6.343a1 1 0 0 0 0 1.414l.536.536L4.757 10H4a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h.757l.707 1.707-.535.536a1 1 0 0 0 0 1.414l1.414 1.414a1 1 0 0 0 1.414 0l.536-.535 1.707.707V20a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-.757l1.707-.708.536.536a1 1 0 0 0 1.414 0l1.414-1.414a1 1 0 0 0 0-1.414l-.535-.536.707-1.707H20a1 1 0 0 0 1-1Z"/>
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                </svg>
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 px-4 py-3 text-sm text-green-700 bg-green-100 border border-green-200 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session('info'))
        <div class="mb-6 px-4 py-3 text-sm text-blue-700 bg-blue-100 border border-blue-200 rounded-lg">
            {{ session('info') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 px-4 py-3 text-sm text-red-700 bg-red-100 border border-red-200 rounded-lg">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Total Down Payment</p>
            <p class="text-2xl font-semibold text-blue-600">Rp{{ number_format($totalDownPayment, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Total Pass Through</p>
            <p class="text-2xl font-semibold text-red-600">Rp{{ number_format($totalPassThrough, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Belum Lunas</p>
            <p class="text-2xl font-semibold text-orange-500">Rp{{ number_format($totalBelumLunas, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-sm text-gray-500">Sudah Lunas</p>
            <p class="text-2xl font-semibold text-green-600">Rp{{ number_format($totalLunas, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Main Content Area --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        {{-- Filter & Search Form --}}
        <form method="GET" action="{{ route('debts.index') }}">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-4">
                    <select name="type_filter" class="border-gray-300 rounded-lg text-sm">
                        <option value="">Tipe: Semua</option>
                        <option value="down_payment" {{ request('type_filter') == 'down_payment' ? 'selected' : '' }}>Down Payment</option>
                        <option value="pass_through" {{ request('type_filter') == 'pass_through' ? 'selected' : '' }}>Pass Through</option>
                    </select>
                    <select name="status_filter" class="border-gray-300 rounded-lg text-sm">
                        <option value="">Status: Semua</option>
                        <option value="{{ \App\Models\Debt::STATUS_BELUM_LUNAS }}" {{ request('status_filter') == \App\Models\Debt::STATUS_BELUM_LUNAS ? 'selected' : '' }}>Belum Lunas</option>
                        <option value="{{ \App\Models\Debt::STATUS_LUNAS }}" {{ request('status_filter') == \App\Models\Debt::STATUS_LUNAS ? 'selected' : '' }}>Lunas</option>
                        <option value="{{ \App\Models\Debt::STATUS_GAGAL }}" {{ request('status_filter') == \App\Models\Debt::STATUS_GAGAL ? 'selected' : '' }}>Gagal</option>
                    </select>
                    <input type="date" name="due_date_from" class="border-gray-300 rounded-lg text-sm" value="{{ request('due_date_from') }}" title="Jatuh tempo dari">
                    <input type="date" name="due_date_to" class="border-gray-300 rounded-lg text-sm" value="{{ request('due_date_to') }}" title="Jatuh tempo sampai">
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Filter</button>
                </div>
                <div class="relative">
                    <input name="search" class="pl-10 pr-4 py-2 border rounded-lg text-sm" placeholder="Cari..." type="text" value="{{ request('search') }}">
                    <button type="submit" class="absolute inset-y-0 left-0 pl-3 flex items-center">
                        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </form>

        {{-- Table Tabs --}}
        @php
            $groupedDebts = $debts->getCollection()->groupBy('type');
            $downPaymentDebts = $groupedDebts->get(\App\Models\Debt::TYPE_DOWN_PAYMENT) ?? collect();
            $passThroughDebts = $groupedDebts->get(\App\Models\Debt::TYPE_PASS_THROUGH) ?? collect();
        @endphp

        <div class="border-b border-gray-200 mb-4">
            <nav class="flex space-x-6" aria-label="Tabs">
                <button type="button"
                    @click="activeTab = '{{ \App\Models\Debt::TYPE_DOWN_PAYMENT }}'"
                    :class="activeTab === '{{ \App\Models\Debt::TYPE_DOWN_PAYMENT }}' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-3 py-2 text-sm font-medium border-b-2 transition">
                    Down Payment
                </button>
                <button type="button"
                    @click="activeTab = '{{ \App\Models\Debt::TYPE_PASS_THROUGH }}'"
                    :class="activeTab === '{{ \App\Models\Debt::TYPE_PASS_THROUGH }}' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-3 py-2 text-sm font-medium border-b-2 transition">
                    Pass Through
                </button>
            </nav>
        </div>

        <div x-cloak x-show="activeTab === '{{ \App\Models\Debt::TYPE_DOWN_PAYMENT }}'">
            @include('debts.partials.debt-table', [
                'debts' => $downPaymentDebts,
                'emptyMessage' => 'Belum ada catatan down payment pada halaman ini.',
            ])
        </div>

        <div x-cloak x-show="activeTab === '{{ \App\Models\Debt::TYPE_PASS_THROUGH }}'">
            @include('debts.partials.debt-table', [
                'debts' => $passThroughDebts,
                'emptyMessage' => 'Belum ada catatan pass through pada halaman ini.',
            ])
        </div>

        <div class="mt-4">
            {{ $debts->links() }}
        </div>
    </div>

    {{-- Modal Pengaturan Kategori --}}
    {{-- Modal Pengaturan Pass Through --}}
    <div x-show="passThroughModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-start overflow-y-auto py-10 px-4 z-50" style="display: none;">
        <div @click.away="passThroughModal = false" class="bg-white rounded-lg p-8 w-full max-w-4xl max-h-[calc(100vh-5rem)] overflow-y-auto">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-2xl font-bold mb-2">Pengaturan Paket Pass Through</h3>
                    <p class="text-sm text-gray-600">Kelola paket pass through untuk pelanggan baru maupun lama. Paket digunakan ketika membuat invoice pass through.</p>
                </div>
                <button type="button" @click="passThroughModal = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @php($passThroughPackageRoutesAvailable = \Illuminate\Support\Facades\Route::has('pass-through.packages.store')
                && \Illuminate\Support\Facades\Route::has('pass-through.packages.update')
                && \Illuminate\Support\Facades\Route::has('pass-through.packages.destroy'))

            @if ($errors->hasBag('passThroughPackage'))
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <h4 class="font-semibold">Gagal menambahkan paket:</h4>
                    <ul class="mt-2 list-disc list-inside space-y-1">
                        @foreach ($errors->getBag('passThroughPackage')->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($errors->hasBag('passThroughPackageUpdate'))
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <h4 class="font-semibold">Gagal memperbarui paket:</h4>
                    <ul class="mt-2 list-disc list-inside space-y-1">
                        @foreach ($errors->getBag('passThroughPackageUpdate')->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($passThroughPackageRoutesAvailable)
                <div class="mt-6">
                    <h4 class="text-lg font-semibold text-gray-800">Tambah Paket Baru</h4>
                    <form method="POST" action="{{ route('pass-through.packages.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Nama Paket</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jenis Pelanggan</label>
                            <select name="customer_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="new" @selected(old('customer_type', 'new') === 'new')>Pelanggan Baru</option>
                                <option value="existing" @selected(old('customer_type') === 'existing')>Pelanggan Lama</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Harga Paket</label>
                            <input type="text" name="package_price" value="{{ old('package_price') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: 10.000.000" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Saldo Harian Terpotong</label>
                            <input type="text" name="daily_deduction" value="{{ old('daily_deduction') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: 500.000" required>
                            <p class="mt-1 text-xs text-gray-500">Nilai ini digunakan sebagai referensi pemotongan saldo harian di menu Pass Through.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Biaya Maintenance</label>
                            <input type="text" name="maintenance_fee" value="{{ old('maintenance_fee') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: 1.500.000" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Biaya Pembuatan Akun Iklan</label>
                            <input type="text" name="account_creation_fee" value="{{ old('account_creation_fee') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: 500.000">
                            <p class="mt-1 text-xs text-gray-500">Untuk pelanggan lama, biaya ini akan diabaikan otomatis.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Biaya Perpanjangan</label>
                            <input type="text" name="renewal_fee" value="{{ old('renewal_fee') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Contoh: 1.000.000">
                        </div>
                        <div class="md:col-span-2 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Simpan Paket</button>
                        </div>
                    </form>
                </div>

                <div class="mt-8">
                    <h4 class="text-lg font-semibold text-gray-800">Daftar Paket</h4>
                    <p class="text-sm text-gray-500">Ubah detail paket yang sudah tersedia atau hapus jika tidak lagi digunakan.</p>
                    <div class="mt-4 space-y-6">
                        @forelse ($passThroughPackages as $package)
                            <div class="rounded-lg border border-gray-200 p-5 shadow-sm">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                    <div>
                                        <h5 class="text-lg font-semibold text-gray-900">{{ $package->name }}</h5>
                                        <p class="text-sm text-gray-500">{{ $package->customerLabel() }} &bull; Harga Paket: Rp{{ number_format($package->packagePrice, 0, ',', '.') }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('pass-through.packages.destroy', $package->id) }}" onsubmit="return confirm('Hapus paket {{ $package->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Hapus</button>
                                    </form>
                                </div>
                                <form method="POST" action="{{ route('pass-through.packages.update', $package->id) }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @csrf
                                    @method('PUT')
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Nama Paket</label>
                                        <input type="text" name="name" value="{{ $package->name }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Jenis Pelanggan</label>
                                        <select name="customer_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                            <option value="new" @selected($package->customerType === 'new')>Pelanggan Baru</option>
                                            <option value="existing" @selected($package->customerType === 'existing')>Pelanggan Lama</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Harga Paket</label>
                                        <input type="text" name="package_price" value="{{ number_format($package->packagePrice, 0, ',', '.') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Saldo Harian Terpotong</label>
                                        <input type="text" name="daily_deduction" value="{{ number_format($package->dailyDeduction, 0, ',', '.') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Biaya Maintenance</label>
                                        <input type="text" name="maintenance_fee" value="{{ number_format($package->maintenanceFee, 0, ',', '.') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Biaya Pembuatan Akun Iklan</label>
                                        <input type="text" name="account_creation_fee" value="{{ number_format($package->accountCreationFee, 0, ',', '.') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Biaya Perpanjangan</label>
                                        <input type="text" name="renewal_fee" value="{{ number_format($package->renewalFee, 0, ',', '.') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    </div>
                                    <div class="md:col-span-2 flex justify-end">
                                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada paket pass through yang tersedia.</p>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <h4 class="font-semibold">Fitur tidak tersedia</h4>
                    <p>Pengelolaan paket pass through sementara tidak dapat digunakan karena rute aplikasi belum diaktifkan.</p>
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <button type="button" @click="passThroughModal = false" class="px-4 py-2 bg-gray-200 rounded-lg">Tutup</button>
            </div>
        </div>
    </div>

    <div x-show="categoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
        <div @click.away="categoryModal = false" class="bg-white rounded-lg p-8 w-full max-w-2xl mx-4 my-10 max-h-[calc(100vh-5rem)] overflow-y-auto">
            <h3 class="text-2xl font-bold mb-2">Pengaturan Pilihan Kategori</h3>
            <p class="text-sm text-gray-600 mb-6">Pilih kategori yang ingin ditampilkan ketika membuat atau melunasi catatan. Biarkan semua checkbox kosong untuk menampilkan seluruh kategori.</p>
            <form method="POST" action="{{ route('debts.category-preferences.update') }}" class="space-y-6">
                @csrf
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Kategori Pemasukan</h4>
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                        @forelse ($incomeCategories as $category)
                            <label class="flex items-start gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="allowed_income_categories[]" value="{{ $category->id }}" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500" {{ $allowedIncomeCategoryIds->contains($category->id) ? 'checked' : '' }}>
                                <span>{{ $category->name }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada kategori pemasukan.</p>
                        @endforelse
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Kategori Pengeluaran</h4>
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                        @forelse ($expenseCategories as $category)
                            <label class="flex items-start gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="allowed_expense_categories[]" value="{{ $category->id }}" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500" {{ $allowedExpenseCategoryIds->contains($category->id) ? 'checked' : '' }}>
                                <span>{{ $category->name }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada kategori pengeluaran.</p>
                        @endforelse
                    </div>
                </div>
                <div class="flex justify-end gap-4">
                    <button type="button" @click="categoryModal = false" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Tambah Catatan Baru --}}
    <div x-show="addModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
        <div @click.away="addModal = false" class="bg-white rounded-lg p-8 w-full max-w-md">
            <h3 class="text-2xl font-bold mb-6">Tambah Catatan Baru</h3>
            <form action="{{ route('debts.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <input type="text" name="description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Pihak Terkait</label>
                        <input type="text" name="related_party" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipe</label>
                        <select name="type" x-model="selectedType" @change="ensureAddFormCategory()" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="down_payment">Down Payment (Orang lain berhutang ke saya)</option>
                            <option value="pass_through">Pass Through (Saya berhutang ke orang lain)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kategori</label>
                        <select name="category_id" x-model="selectedCategoryId" :disabled="addFormCategories().length === 0" :required="addFormCategories().length > 0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="" disabled x-show="addFormCategories().length === 0">Tidak ada kategori tersedia</option>
                            <template x-for="category in addFormCategories()" :key="'add-' + category.id">
                                <option :value="category.id" x-text="category.name"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Kategori akan digunakan saat pelunasan dicatat.</p>
                        @error('category_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Nilai</label>
                        <input type="text" x-model="formattedAmount" @input="formatCurrencyInput($event.target.value)" inputmode="numeric" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" autocomplete="off" required>
                        <input type="hidden" name="amount" :value="rawAmount">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jatuh Tempo (Opsional)</label>
                        <input type="date" name="due_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-4">
                    <button type="button" @click="addModal = false" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Catat Pembayaran --}}
    <div x-show="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
        <div @click.away="paymentModal = false" class="bg-white rounded-lg p-8 w-full max-w-md">
            <h3 class="text-2xl font-bold mb-2">Catat Pembayaran</h3>
            <p class="text-gray-600 mb-6" x-text="selectedDebt.description"></p>
            <form :action="`/debts/${selectedDebt.id}/pay`" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jumlah Pembayaran</label>
                        <input type="number" name="payment_amount" step="any" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kategori Pelunasan</label>
                        <select name="category_id" x-model="paymentCategoryId" :disabled="paymentCategories().length === 0" :required="paymentCategories().length > 0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="" disabled x-show="paymentCategories().length === 0">Tidak ada kategori tersedia</option>
                            <template x-for="category in paymentCategories()" :key="'payment-' + category.id">
                                <option :value="category.id" x-text="category.name"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Kategori mengikuti tipe catatan yang dipilih.</p>
                        @if ($errors->has('category_id'))
                            <p class="mt-1 text-sm text-red-600">{{ $errors->first('category_id') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Pembayaran</label>
                        <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Catatan (Opsional)</label>
                        <textarea name="notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-4">
                    <button type="button" @click="paymentModal = false" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="detailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
        <div @click.away="detailModal = false" class="bg-white rounded-lg p-8 w-full max-w-2xl">
            <h3 class="text-2xl font-bold mb-2">Riwayat Pembayaran</h3>
            <p class="text-gray-600 mb-6" x-text="selectedDebt.description"></p>

            {{-- Info Ringkas --}}
            <div class="grid grid-cols-3 gap-4 mb-6 text-center">
                <div>
                    <p class="text-sm text-gray-500">Total Nilai</p>
                    <p class="font-semibold" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(selectedDebt.amount)"></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Telah Dibayar</p>
                    <p class="font-semibold text-green-600" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(selectedDebt.paid_amount)"></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Sisa Tagihan</p>
                    <p class="font-semibold text-red-600" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(selectedDebt.remaining_amount)"></p>
                </div>
            </div>

            {{-- Tabel Riwayat Cicilan --}}
            <div class="overflow-y-auto max-h-64 border rounded-lg">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">Jumlah</th>
                            <th class="px-4 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y bg-white">
                        <template x-if="selectedDebt.payments && selectedDebt.payments.length > 0">
                            <template x-for="payment in selectedDebt.payments" :key="payment.id">
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-500" x-text="new Date(payment.payment_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })"></td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(payment.amount)"></td>
                                    <td class="px-4 py-3 text-sm text-gray-500" x-text="payment.notes || '-'"></td>
                                </tr>
                            </template>
                        </template>
                        <template x-if="!selectedDebt.payments || selectedDebt.payments.length === 0">
                            <tr>
                                <td colspan="3" class="text-center py-6 text-gray-500">Belum ada pembayaran yang tercatat.</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" @click="detailModal = false" class="px-4 py-2 bg-gray-200 rounded-lg">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection
