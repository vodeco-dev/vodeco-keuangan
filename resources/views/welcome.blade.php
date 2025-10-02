<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    {{-- Tambahkan kelas 'animated-gradient-bg' untuk background baru --}}
    <body class="antialiased" style="background-image: url('{{ asset('background-vodeco.jpg') }}'); background-size: cover; background-position: center;">
        <div class="relative sm:flex sm:justify-center sm:items-center min-h-screen selection:bg-red-500 selection:text-white">


            <div class="max-w-7xl mx-auto p-6 lg:p-8">
                <div class="flex flex-col items-start justify-center text-left">
                    <h1 class="text-4xl lg:text-6xl font-extrabold text-white">
                        CV VODECO DIGITAL MEDIATAMA
                    </h1>
                    <p class="mt-4 text-lg lg:text-2xl text-gray-300">
                        Finance App By Vodeco
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('login') }}" class="inline-block px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75 text-center">
                            Log in
                        </a>
                        <a href="{{ route('invoices.public.create') }}" class="inline-block px-8 py-3 bg-white/90 text-blue-700 font-semibold rounded-lg shadow-md hover:bg-white focus:outline-none focus:ring-2 focus:ring-white/70 focus:ring-opacity-75 text-center">
                            Buat Invoice
                        </a>
                        <button id="open-invoice-status" type="button" class="inline-block px-8 py-3 bg-emerald-500/90 text-white font-semibold rounded-lg shadow-md hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-opacity-75">
                            Cek Status Invoice
                        </button>
                    </div>
                </div>
            </div>

            <div id="invoice-status-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 px-4">
                <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                    <button id="close-invoice-status" type="button" class="absolute right-3 top-3 rounded-full bg-gray-100 p-2 text-gray-600 transition hover:bg-gray-200" aria-label="Tutup dialog">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <h2 class="text-2xl font-semibold text-gray-800">Cek Status Invoice</h2>
                    <p class="mt-1 text-sm text-gray-500">Masukkan nomor invoice untuk melihat status terbaru.</p>

                    <form id="invoice-status-form" class="mt-6 space-y-4">
                        <div>
                            <label for="invoice-number" class="block text-sm font-medium text-gray-700">Nomor Invoice</label>
                            <input id="invoice-number" type="text" name="number" required class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="Contoh: INV-001" />
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75">
                            Cek Sekarang
                        </button>
                    </form>

                    <div id="invoice-status-result" class="mt-4 hidden rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700"></div>
                </div>
            </div>

        </div>




        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('invoice-status-modal');
                const openButton = document.getElementById('open-invoice-status');
                const closeButton = document.getElementById('close-invoice-status');
                const form = document.getElementById('invoice-status-form');
                const resultBox = document.getElementById('invoice-status-result');

                const toggleModal = (show) => {
                    if (show) {
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    } else {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                        form.reset();
                        resultBox.classList.add('hidden');
                        resultBox.textContent = '';
                    }
                };

                openButton?.addEventListener('click', () => toggleModal(true));
                closeButton?.addEventListener('click', () => toggleModal(false));

                modal?.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        toggleModal(false);
                    }
                });

                form?.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const number = document.getElementById('invoice-number').value.trim();
                    if (!number) {
                        resultBox.textContent = 'Nomor invoice wajib diisi.';
                        resultBox.classList.remove('hidden');
                        return;
                    }

                    resultBox.classList.remove('hidden');
                    resultBox.innerHTML = '<span class="text-gray-600">Memeriksa status invoice...</span>';

                    try {
                        const params = new URLSearchParams({ number });
                        const response = await fetch(`{{ route('invoices.public.check-status') }}?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            resultBox.innerHTML = `<span class="text-red-600">${data.message ?? 'Terjadi kesalahan saat memeriksa invoice.'}</span>`;
                            return;
                        }

                        const clientName = data.client_name ? `<p class="mt-1"><span class="font-medium">Klien:</span> ${data.client_name}</p>` : '';

                        resultBox.innerHTML = `
                            <p><span class="font-medium">Nomor Invoice:</span> ${data.number}</p>
                            <p class="mt-1"><span class="font-medium">Status:</span> ${data.status}</p>
                            ${clientName}
                            <p class="mt-1"><span class="font-medium">Jatuh Tempo:</span> ${data.due_date ?? '-'}</p>
                            <p class="mt-1"><span class="font-medium">Total Tagihan:</span> Rp ${data.total}</p>
                        `;
                    } catch (error) {
                        resultBox.innerHTML = '<span class="text-red-600">Tidak dapat terhubung ke server. Silakan coba lagi.</span>';
                    }
                });
            });
        </script>
    </body>
</html>
