<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Finance App') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    {{-- Tambahkan kelas 'animated-gradient-bg' untuk background baru --}}
    <body class="antialiased" style="background-image: url('{{ asset('background-vodeco.jpg') }}'); background-size: cover; background-position: center;">
        <div class="relative sm:flex sm:justify-center sm:items-center min-h-screen selection:bg-red-500 selection:text-white flex items-center justify-center">
            <div class="max-w-7xl mx-auto p-6 lg:p-8 w-full flex flex-col items-center justify-center">
            <div class="flex flex-col items-start justify-center text-left w-full">
                <h1 class="text-3xl sm:text-4xl lg:text-6xl font-extrabold text-white leading-tight">
                CV VODECO DIGITAL MEDIATAMA
                </h1>
                <p class="mt-3 sm:mt-4 text-base sm:text-lg lg:text-2xl text-gray-300">
                Finance App By Vodeco
                </p>
                <div class="mt-6 sm:mt-8 flex flex-col sm:flex-row gap-3 sm:gap-4 w-full">
                <a href="{{ route('login') }}" class="w-full sm:w-auto inline-block px-6 py-3 sm:px-8 sm:py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75 text-center text-base sm:text-lg">
                    Log in
                </a>
                <a href="{{ route('invoices.public.create') }}" class="w-full sm:w-auto inline-block px-6 py-3 sm:px-8 sm:py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75 text-center text-base sm:text-lg">
                    Buat Invoice
                </a>
                </div>
            </div>
            </div>
        </div>
    </body>
</html>
