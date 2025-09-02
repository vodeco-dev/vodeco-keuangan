<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buat Invoice Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('invoices.store') }}" method="POST" class="p-6">
                    @csrf

                    {{-- Informasi Klien --}}
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Klien</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="client_name" class="block text-sm font-medium text-gray-700">Nama Klien</label>
                            <input type="text" name="client_name" id="client_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label for="client_email" class="block text-sm font-medium text-gray-700">Email Klien</label>
                            <input type="email" name="client_email" id="client_email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div class="md:col-span-2">
                            <label for="client_address" class="block text-sm font-medium text-gray-700">Alamat Klien</label>
                            <textarea name="client_address" id="client_address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required></textarea>
                        </div>
                    </div>

                    {{-- Informasi Invoice --}}
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Detail Invoice</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="number" class="block text-sm font-medium text-gray-700">Nomor Invoice</label>
                            <input type="text" name="number" id="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-700">Tanggal Jatuh Tempo</label>
                            <input type="date" name="due_date" id="due_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>

                    {{-- Item Invoice --}}
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Item</h3>
                    <div id="invoice-items" class="space-y-4">
                        <div class="grid grid-cols-12 gap-4 invoice-item">
                            <div class="col-span-5">
                                <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                                <input type="text" name="items[0][description]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Kuantitas</label>
                                <input type="number" name="items[0][quantity]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm quantity" value="1" min="1" required>
                            </div>
                            <div class="col-span-3">
                                <label class="block text-sm font-medium text-gray-700">Harga</label>
                                <input type="number" step="0.01" name="items[0][price]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm price" required>
                            </div>
                            <div class="col-span-2 flex items-end">
                                <button type="button" class="text-red-500 hover:text-red-700 remove-item">Hapus</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="button" id="add-item" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Tambah Item</button>
                    </div>

                    {{-- Total --}}
                    <div class="mt-6 pt-6 border-t border-gray-200 flex justify-end">
                        <div class="text-right">
                            <p class="text-lg font-medium text-gray-900">Total: <span id="total-amount">Rp 0</span></p>
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
            let itemIndex = 1;

            document.getElementById('add-item').addEventListener('click', function () {
                const itemHtml = `
                    <div class="grid grid-cols-12 gap-4 invoice-item">
                        <div class="col-span-5">
                            <input type="text" name="items[${itemIndex}][description]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div class="col-span-2">
                            <input type="number" name="items[${itemIndex}][quantity]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm quantity" value="1" min="1" required>
                        </div>
                        <div class="col-span-3">
                            <input type="number" step="0.01" name="items[${itemIndex}][price]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm price" required>
                        </div>
                        <div class="col-span-2 flex items-end">
                            <button type="button" class="text-red-500 hover:text-red-700 remove-item">Hapus</button>
                        </div>
                    </div>
                `;
                document.getElementById('invoice-items').insertAdjacentHTML('beforeend', itemHtml);
                itemIndex++;
            });

            document.getElementById('invoice-items').addEventListener('click', function (e) {
                if (e.target && e.target.classList.contains('remove-item')) {
                    e.target.closest('.invoice-item').remove();
                    updateTotal();
                }
            });

            document.getElementById('invoice-items').addEventListener('input', function (e) {
                if (e.target && (e.target.classList.contains('quantity') || e.target.classList.contains('price'))) {
                    updateTotal();
                }
            });

            function updateTotal() {
                let total = 0;
                document.querySelectorAll('.invoice-item').forEach(function (item) {
                    const quantity = item.querySelector('.quantity').value || 0;
                    const price = item.querySelector('.price').value || 0;
                    total += quantity * price;
                });
                document.getElementById('total-amount').textContent = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(total);
            }

            // Initial total calculation
            updateTotal();
        });
    </script>
    @endpush
</x-app-layout>