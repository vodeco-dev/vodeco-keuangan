<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-900" style="background-image: url('{{ asset('background-vodeco.jpg') }}'); background-size: cover; background-position: center;">
        <div class="min-h-screen flex flex-col lg:flex-row">
            <aside class="hidden lg:flex lg:w-1/2 bg-white text-gray-900">
                <div class="flex flex-col justify-center items-center w-full px-12 py-10">
                    <img src="{{ asset('login-page-vodeco.png') }}" alt="Tim Vodeco" class="w-full max-w-lg rounded-3xl shadow-xl ring-1 ring-indigo-100">
                    <div class="text-sm text-gray-500 mt-4">
                        <p>© {{ now()->year }} Vodeco. All rights reserved.</p>
                    </div>
                </div>
            </aside>

            <main class="flex-1 text-white">
                <div class="flex flex-col justify-center px-6 py-12 sm:px-12">
                    <div class="mx-auto w-full max-w-md space-y-10">
                        <div class="flex flex-col items-center space-y-4 text-center">
                            <img src="{{ asset('logo-vodeco-dark-mode.png') }}" alt="Logo Vodeco" class="h-14">
                            <div>
                                <h1 class="mt-2 text-3xl font-bold">Masuk ke Dashboard Vodeco</h1>
                                <p class="mt-2 text-sm text-indigo-100">Silakan masuk menggunakan email dan kata sandi yang terdaftar.</p>
                            </div>
                        </div>

                        <div class="rounded-3xl bg-white/10 p-8 shadow-xl shadow-indigo-900/20 backdrop-blur-2xl border border-white/30">
                            {{ $slot }}
                        </div>

                        <div class="text-center text-sm text-indigo-100/80">
                            <p>Dengan masuk, Anda menyetujui <a href="#" class="font-medium text-white underline decoration-indigo-200/60 underline-offset-4 hover:text-indigo-200">Kebijakan Privasi</a>.</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
