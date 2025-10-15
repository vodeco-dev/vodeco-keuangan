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
@endphp
{{-- Tambahkan 'detailModal' ke dalam x-data untuk mengontrol modal baru --}}
<div x-data="{
        addModal: false,
        paymentModal: false,
        detailModal: false,
        categoryModal: false,
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
        activeTab: '{{ \App\Models\Debt::TYPE_DOWN_PAYMENT }}',
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
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Manajemen Down Payment</h2>
        <div class="flex items-center gap-3">
            <button @click="addModal = true" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Tambah Catatan Baru</span>
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
            $downPaymentDebts = $debts->getCollection();
        @endphp

        @include('debts.partials.debt-table', [
            'debts' => $downPaymentDebts,
            'emptyMessage' => 'Belum ada catatan down payment pada halaman ini.',
        ])

        <div class="mt-4">
            {{ $debts->links() }}
        </div>
    </div>

    {{-- Modal Pengaturan Kategori --}}
    