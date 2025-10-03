@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white mb-6">Edit Invoice</h1>

        @php
            $oldItems = old('items', $invoice->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => (string) (int) round($item->price),
                'category_id' => $item->category_id,
            ])->toArray());
            $categoryOptions = $incomeCategories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values();
        @endphp

        <form action="{{ route('invoices.update', $invoice) }}" method="POST" id="invoice-form" data-category-options='@json($categoryOptions)' class="bg-white dark:bg-gray-900 shadow rounded-lg p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="customer_service_id" class="block text-sm font-medium text-gray-700">Customer Service</label>
                <select name="customer_service_id" id="customer_service_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Pilih customer service</option>
                    @foreach($customerServices as $customerService)
                        <option value="{{ $customerService->id }}" @selected(old('customer_service_id', $invoice->customer_service_id) == $customerService->id)>
                            {{ $customerService->name }}
                        </option>
                    @endforeach
                </select>
                @error('customer_service_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="client_name" class="block text-sm font-medium text-gray-700">Nama Klien</label>
                    <input type="text" name="client_name" id="client_name" value="{{ old('client_name', $invoice->client_name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    @error('client_name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="client_whatsapp" class="block text-sm font-medium text-gray-700">Nomor WhatsApp Klien</label>
                    <input type="text" name="client_whatsapp" id="client_whatsapp" value="{{ old('client_whatsapp', $invoice->client_whatsapp) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    @error('client_whatsapp')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label for="client_address" class="block text-sm font-medium text-gray-700">Alamat Klien</label>
                    <textarea name="client_address" id="client_address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>{{ old('client_address', $invoice->client_address) }}</textarea>
                    @error('client_address')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="issue_date" class="block text-sm font-medium text-gray-700">Tanggal Terbit</label>
                    <input type="date" name="issue_date" id="issue_date" value="{{ old('issue_date', optional($invoice->issue_date)->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('issue_date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700">Tanggal Jatuh Tempo</label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('due_date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Daftar Item</h2>
                    <button type="button" id="add-item" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Tambah Item</button>
                </div>
                <div id="invoice-items" class="space-y-4">
                    @foreach($oldItems as $index => $item)
                        <div class="grid grid-cols-12 gap-4 invoice-item bg-gray-50 p-4 rounded-lg">
                            <div class="col-span-12 md:col-span-4">
                                <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                <textarea name="items[{{ $index }}][description]" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm description" required>{{ $item['description'] ?? '' }}</textarea>
                                @error('items.' . $index . '.description')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-span-12 md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700">Kategori</label>
                                <select name="items[{{ $index }}][category_id]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm category-select" required>
                                    <option value="">Pilih kategori pemasukan</option>
                                    @foreach($incomeCategories as $category)
                                        <option value="{{ $category->id }}" @selected(($item['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('items.' . $index . '.category_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-span-6 md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Kuantitas</label>
                                <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm quantity" min="1" required>
                                @error('items.' . $index . '.quantity')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-span-6 md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Harga Satuan</label>
                                <input type="text" name="items[{{ $index }}][price]" value="{{ $item['price'] ?? '' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm price-input" required>
                                @error('items.' . $index . '.price')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-span-12 md:col-span-1 flex items-end justify-end">
                                <button type="button" class="text-red-500 hover:text-red-700 remove-item">Hapus</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="down_payment_due" class="block text-sm font-medium text-gray-700">Rencana Down Payment</label>
                <input
                    type="text"
                    name="down_payment_due"
                    id="down_payment_due"
                    value="{{ old('down_payment_due', $invoice->down_payment_due !== null ? (string) (int) round($invoice->down_payment_due) : '') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm price-input"
                    placeholder="Contoh: 5.000.000"
                >
                <p class="mt-1 text-xs text-gray-500">Opsional. Nilai ini akan diusulkan sebagai nominal pembayaran awal ketika mencatat pembayaran.</p>
                @error('down_payment_due')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-3 text-base font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Perbarui Invoice</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const invoiceForm = document.getElementById('invoice-form');
        const categoryOptions = JSON.parse(invoiceForm.dataset.categoryOptions || '[]');
        let itemIndex = {{ count($oldItems) }};

        const invoiceItemsContainer = document.getElementById('invoice-items');

        function formatPrice(input) {
            const raw = (input.value || '').replace(/\D/g, '');
            input.dataset.rawValue = raw;
            input.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        }

        function initPriceInput(input) {
            formatPrice(input);
            input.addEventListener('input', function () {
                formatPrice(input);
            });
            input.addEventListener('blur', function () {
                formatPrice(input);
            });
        }

        function buildCategoryOptions(selected = '') {
            return ['<option value="">Pilih kategori pemasukan</option>'].concat(
                categoryOptions.map(function (option) {
                    const isSelected = String(option.id) === String(selected);
                    return `<option value="${option.id}"${isSelected ? ' selected' : ''}>${option.name}</option>`;
                })
            ).join('');
        }

        document.querySelectorAll('.price-input').forEach(initPriceInput);

        document.getElementById('add-item').addEventListener('click', function () {
            const wrapper = document.createElement('div');
            wrapper.className = 'grid grid-cols-12 gap-4 invoice-item bg-gray-50 p-4 rounded-lg';
            wrapper.innerHTML = `
                <div class="col-span-12 md:col-span-4">
                    <textarea name="items[${itemIndex}][description]" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm description" placeholder="Deskripsi" required></textarea>
                </div>
                <div class="col-span-12 md:col-span-3">
                    <select name="items[${itemIndex}][category_id]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm category-select" required>
                        ${buildCategoryOptions(categoryOptions[0]?.id ?? '')}
                    </select>
                </div>
                <div class="col-span-6 md:col-span-2">
                    <input type="number" name="items[${itemIndex}][quantity]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm quantity" value="1" min="1" required>
                </div>
                <div class="col-span-6 md:col-span-2">
                    <input type="text" name="items[${itemIndex}][price]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm price-input" required>
                </div>
                <div class="col-span-12 md:col-span-1 flex items-end justify-end">
                    <button type="button" class="text-red-500 hover:text-red-700 remove-item">Hapus</button>
                </div>
            `;

            invoiceItemsContainer.appendChild(wrapper);

            wrapper.querySelectorAll('.price-input').forEach(initPriceInput);
            itemIndex++;
        });

        invoiceItemsContainer.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-item')) {
                const item = event.target.closest('.invoice-item');
                if (item) {
                    item.remove();
                }
            }
        });

        invoiceForm.addEventListener('submit', function () {
            document.querySelectorAll('.price-input').forEach(function (input) {
                const raw = input.dataset.rawValue || '';
                input.value = raw;
            });
        });
    });
</script>
@endpush
