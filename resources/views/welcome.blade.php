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
                    </div>
                </div>
            </div>

        </div>




    </body>
</html>
