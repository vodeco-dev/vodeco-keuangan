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
                @endphp
                <form action="{{ route('invoices.store') }}" method="POST" class="p-6" id="invoice-form" data-category-options='@json($categoryOptions)'>
                    @csrf

                    {{-- Customer Service --}}
                    <div class="mb-6">
                        <label for="customer_service_id" class="block text-sm font-medium text-gray-700">Customer Service</label>
                        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                            <select name="customer_service_id" id="customer_service_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Pilih customer service</option>
                                @foreach($customerServices as $customerService)
                                    <option value="{{ $customerService->id }}" @selected(old('customer_service_id') == $customerService->id)>
                                        {{ $customerService->name }}
                                    </option>
                                @endforeach
                            </select>
                            <a href="{{ route('customer-services.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                Tambah Customer Service
                            </a>
                        </div>
                        @error('customer_service_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

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
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Item</h3>
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

                    <div class="mt-4">
                        <button type="button" id="add-item" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Tambah Item</button>
                    </div>

                    {{-- Total --}}
                    <div class="mt-6 pt-6 border-t border-gray-200 flex justify-end">
                        <div class="text-right">
                            <p class="text-lg font-medium text-gray-900 dark:text-white">Total: <span id="total-amount">Rp 0</span></p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="px-6 py-3 text-base font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Simpan Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const invoiceForm = document.getElementById('invoice-form');
            const categoryOptions = JSON.parse(invoiceForm.dataset.categoryOptions || '[]');
            let itemIndex = {{ count($oldItems) }};

            const invoiceItemsContainer = document.getElementById('invoice-items');
            const totalAmountElement = document.getElementById('total-amount');

            function formatPrice(input) {
                const raw = (input.value || '').replace(/\D/g, '');
                input.dataset.rawValue = raw;
                input.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
            }

            function initPriceInput(input) {
                formatPrice(input);
                input.addEventListener('input', function () {
                    formatPrice(input);
                    updateTotal();
                });
                input.addEventListener('blur', function () {
                    formatPrice(input);
                });
            }

            function updateTotal() {
                let total = 0;
                document.querySelectorAll('#invoice-items .invoice-item').forEach(function (item) {
                    const quantityValue = item.querySelector('.quantity')?.value || '0';
                    const priceInput = item.querySelector('.price-input');
                    const quantity = parseInt(quantityValue, 10) || 0;
                    const price = parseInt(priceInput?.dataset.rawValue || '0', 10) || 0;
                    total += quantity * price;
                });

                totalAmountElement.textContent = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(total || 0);
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
            document.querySelectorAll('.quantity').forEach(function (input) {
                input.addEventListener('input', updateTotal);
            });

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
                wrapper.querySelectorAll('.quantity').forEach(function (input) {
                    input.addEventListener('input', updateTotal);
                });

                itemIndex++;
                updateTotal();
            });

            invoiceItemsContainer.addEventListener('click', function (event) {
                if (event.target.classList.contains('remove-item')) {
                    const item = event.target.closest('.invoice-item');
                    if (item) {
                        item.remove();
                        updateTotal();
                    }
                }
            });

            invoiceForm.addEventListener('submit', function () {
                document.querySelectorAll('.price-input').forEach(function (input) {
                    const raw = input.dataset.rawValue || '';
                    input.value = raw;
                });
            });

            updateTotal();
        });
    </script>
    @endpush
</x-app-layout>
