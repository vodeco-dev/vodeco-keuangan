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
                <form action="{{ route('invoices.store') }}" method="POST" class="p-6" id="invoice-form">
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
                    <div class="mt-8">
                        <x-invoice.transaction-tabs
                            form-id="invoice-form"
                            :items="$oldItems"
                            :category-options="$categoryOptions"
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
                        />
                    </div>


                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="px-6 py-3 text-base font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Simpan Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-app-layout>
