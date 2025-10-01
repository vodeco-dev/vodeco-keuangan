<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Buat Invoice - Vodeco</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen py-10">
        <div class="max-w-5xl mx-auto px-4">
            <div class="mb-10 text-center">
                <img src="{{ asset('logo-vodeco-dark-mode.png') }}" alt="Logo Vodeco" class="mx-auto h-16">
                <h1 class="mt-4 text-3xl font-semibold text-gray-900">Formulir Pembuatan Invoice</h1>
                <p class="mt-2 text-gray-600">Isi detail berikut untuk membuat invoice. Setelah formulir dikirim, file PDF akan terunduh secara otomatis.</p>
            </div>

            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-8 md:px-10">
                    @if ($errors->any())
                        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                            <h2 class="mb-2 font-semibold">Terjadi kesalahan:</h2>
                            <ul class="list-disc space-y-1 pl-5 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('invoices.public.store') }}" method="POST" class="space-y-10">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label for="client_name" class="block text-sm font-medium text-gray-700">Nama Klien</label>
                                    <input type="text" name="client_name" id="client_name" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ old('client_name') }}" required>
                                </div>
                                <div>
                                    <label for="client_email" class="block text-sm font-medium text-gray-700">Email Klien</label>
                                    <input type="email" name="client_email" id="client_email" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ old('client_email') }}" required>
                                </div>
                                <div>
                                    <label for="due_date" class="block text-sm font-medium text-gray-700">Tanggal Jatuh Tempo</label>
                                    <input type="date" name="due_date" id="due_date" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ old('due_date') }}">
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label for="customer_service_name" class="block text-sm font-medium text-gray-700">Customer Service</label>
                                    <input type="text" name="customer_service_name" id="customer_service_name" list="customer-service-options"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Ketik nama customer service"
                                        value="{{ old('customer_service_name') }}"
                                        required>
                                    <datalist id="customer-service-options">
                                        @foreach ($customerServices as $customerService)
                                            <option value="{{ $customerService->name }}">{{ $customerService->name }}</option>
                                        @endforeach
                                    </datalist>
                                </div>
                                <div>
                                    <label for="client_address" class="block text-sm font-medium text-gray-700">Alamat Klien</label>
                                    <textarea name="client_address" id="client_address" rows="6" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('client_address') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900">Daftar Item</h2>
                                <button type="button" id="add-item" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    + Tambah Item
                                </button>
                            </div>

                            <div id="invoice-items" class="space-y-4">
                                <div class="grid grid-cols-12 gap-4 invoice-item bg-gray-50 p-4 rounded-xl">
                                    <div class="col-span-12 md:col-span-5">
                                        <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                        <input type="text" name="items[0][description]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    </div>
                                    <div class="col-span-6 md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Kuantitas</label>
                                        <input type="number" name="items[0][quantity]" class="quantity mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="1" min="1" required>
                                    </div>
                                    <div class="col-span-6 md:col-span-3">
                                        <label class="block text-sm font-medium text-gray-700">Harga Satuan</label>
                                        <input type="number" step="0.01" name="items[0][price]" class="price mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    </div>
                                    <div class="col-span-12 md:col-span-2 flex items-end justify-end">
                                        <button type="button" class="remove-item text-sm font-semibold text-red-600 hover:text-red-700">Hapus</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-2 border-t border-gray-200 pt-6">
                            <p class="text-lg font-semibold text-gray-900">Total Invoice</p>
                            <p id="total-amount" class="text-2xl font-bold text-indigo-600">Rp 0</p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-green-600 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                Buat & Unduh Invoice
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <p class="mt-8 text-center text-sm text-gray-500">
                Setelah invoice dibuat, Anda juga dapat membagikan link publik kepada klien menggunakan token yang tersedia di dashboard customer service.
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let itemIndex = 1;

            const invoiceItemsContainer = document.getElementById('invoice-items');
            const totalAmountElement = document.getElementById('total-amount');

            document.getElementById('add-item').addEventListener('click', () => {
                const template = document.createElement('template');
                template.innerHTML = `
                    <div class="grid grid-cols-12 gap-4 invoice-item bg-gray-50 p-4 rounded-xl">
                        <div class="col-span-12 md:col-span-5">
                            <input type="text" name="items[${itemIndex}][description]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Deskripsi" required>
                        </div>
                        <div class="col-span-6 md:col-span-2">
                            <input type="number" name="items[${itemIndex}][quantity]" class="quantity mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="1" min="1" required>
                        </div>
                        <div class="col-span-6 md:col-span-3">
                            <input type="number" step="0.01" name="items[${itemIndex}][price]" class="price mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Harga" required>
                        </div>
                        <div class="col-span-12 md:col-span-2 flex items-end justify-end">
                            <button type="button" class="remove-item text-sm font-semibold text-red-600 hover:text-red-700">Hapus</button>
                        </div>
                    </div>
                `.trim();

                invoiceItemsContainer.appendChild(template.content.firstChild);
                itemIndex++;
                updateTotal();
            });

            invoiceItemsContainer.addEventListener('click', (event) => {
                if (event.target.classList.contains('remove-item')) {
                    const item = event.target.closest('.invoice-item');
                    if (item) {
                        item.remove();
                        updateTotal();
                    }
                }
            });

            invoiceItemsContainer.addEventListener('input', (event) => {
                if (event.target.classList.contains('quantity') || event.target.classList.contains('price')) {
                    updateTotal();
                }
            });

            function updateTotal() {
                let total = 0;
                document.querySelectorAll('#invoice-items .invoice-item').forEach((item) => {
                    const quantity = parseFloat(item.querySelector('.quantity')?.value || 0);
                    const price = parseFloat(item.querySelector('.price')?.value || 0);
                    total += quantity * price;
                });

                totalAmountElement.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR'
                }).format(total || 0);
            }

            updateTotal();
        });
    </script>
</body>
</html>
