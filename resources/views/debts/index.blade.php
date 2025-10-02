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

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Deskripsi</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Pihak Terkait</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Tipe</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Dibayar</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Sisa</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Progres</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Jatuh Tempo</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($debts as $debt)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $debt->description }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $debt->related_party }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($debt->type == 'down_payment')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Down Payment</span>
                            @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Pass Through</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">Rp{{ number_format($debt->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">Rp{{ number_format($debt->paid_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">Rp{{ number_format($debt->remaining_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="{{ $debt->type == 'down_payment' ? 'bg-blue-600' : 'bg-red-600' }} h-2.5 rounded-full" style="width: {{ $debt->progress }}%"></div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $debt->due_date ? \Carbon\Carbon::parse($debt->due_date)->isoFormat('D MMM YYYY') : '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($debt->status == \App\Models\Debt::STATUS_LUNAS)
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Lunas</span>
                            @elseif ($debt->status == \App\Models\Debt::STATUS_GAGAL)
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Gagal</span>
                            @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800">Belum Lunas</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if ($debt->status == \App\Models\Debt::STATUS_BELUM_LUNAS)
                                <button @click='openPaymentModal({{ $debt }})' class="text-blue-600 hover:text-blue-900" title="Tambah Pembayaran">
                                    <svg fill="none" height="20" stroke="currentColor" viewBox="0 0 24 24" width="20">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </button>

                                <a href="{{ route('debts.edit', $debt) }}" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.3 4.8 2.9 2.9M7 7H4a1 1 0 0 0-1 1v10c0 .6.4 1 1 1h11c.6 0 1-.4 1-1v-4.5m2.4-10a2 2 0 0 1 0 3l-6.8 6.8L8 18l.7-3.6 6.9-6.8a2 2 0 0 1 2.8 0Z"/>
                                    </svg>
                                </a>

                                <form action="{{ route('debts.fail', $debt) }}" method="POST" class="inline" onsubmit="return confirm('Tandai catatan ini sebagai gagal project?');">
                                    @csrf
                                    <button type="submit" class="text-red-500 hover:text-red-800" title="Tandai Gagal">
                                        <svg fill="none" height="20" stroke="currentColor" viewBox="0 0 24 24" width="20">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.071 19h13.858c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.339 16c-.77 1.333.192 3 1.732 3Z"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif

                                {{-- Tombol Detail Riwayat --}}
                                <button @click="detailModal = true; selectedDebt = {{ $debt }}" class="text-gray-500 hover:text-gray-800 dark:text-white" title="Lihat Riwayat Pembayaran">
                                    <svg fill="none" height="20" stroke="currentColor" viewBox="0 0 24 24" width="20">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>

                                <form action="{{ route('debts.destroy', $debt) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus catatan ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-800" title="Hapus">
                                        <svg fill="none" height="20" stroke="currentColor" viewBox="0 0 24 24" width="20">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-8 text-gray-500">Tidak ada data untuk ditampilkan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $debts->links() }}
        </div>
    </div>

    {{-- Modal Pengaturan Kategori --}}
    <div x-show="categoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
        <div @click.away="categoryModal = false" class="bg-white rounded-lg p-8 w-full max-w-2xl">
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
