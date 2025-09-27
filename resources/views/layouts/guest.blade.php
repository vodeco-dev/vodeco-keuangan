<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" href="{{ asset('favicon.png') }}">
        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-900">
        <div class="min-h-screen flex flex-col lg:flex-row">
            <aside class="hidden lg:flex lg:w-1/2 bg-white text-gray-900">
                <div class="flex flex-col justify-between w-full px-12 py-10">
                    <div class="flex items-center justify-start">
                        <a href="/" class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-indigo-500 hover:text-indigo-600">
                            <span class="inline-flex h-2 w-2 rounded-full bg-indigo-500"></span>
                            <span>Kembali ke Vodeco</span>
                        </a>
                    </div>

                    <div class="mt-12 flex-1">
                        <div class="max-w-lg">
                            <p class="text-sm font-semibold uppercase tracking-[0.35em] text-indigo-500">3+ Team</p>
                            <p class="mt-4 text-5xl font-bold text-gray-900">Memberdayakan Bisnis Anda dengan Inovasi Digital</p>
                            <p class="mt-6 text-base text-gray-600">Lebih dari 5 tahun pengalaman membantu lebih dari 23 orang profesional mewujudkan solusi digital terbaik.</p>
                        </div>

                        <div class="mt-10">
                            <img src="{{ asset('login-page-vodeco.png') }}" alt="Tim Vodeco" class="w-full max-w-xl rounded-3xl shadow-xl ring-1 ring-indigo-100">
                        </div>
                    </div>

                    <div class="mt-12 text-sm text-gray-500">
                        <p>© {{ now()->year }} Vodeco. All rights reserved.</p>
                    </div>
                </div>
            </aside>

            <main class="flex-1 bg-gradient-to-b from-[#1B1741] via-[#261F6E] to-[#3A1E94] text-white">
                <div class="flex min-h-screen flex-col justify-center px-6 py-12 sm:px-12">
                    <div class="mx-auto w-full max-w-md space-y-10">
                        <div class="flex flex-col items-center space-y-4 text-center">
                            <img src="{{ asset('logo-vodeco-dark-mode.png') }}" alt="Logo Vodeco" class="h-14">
                            <div>
                                <p class="text-lg font-semibold tracking-wide text-indigo-100">Let’s Lift Your Brand</p>
                                <h1 class="mt-2 text-3xl font-bold">Masuk ke Dashboard Vodeco</h1>
                                <p class="mt-2 text-sm text-indigo-100">Silakan masuk menggunakan email dan kata sandi yang terdaftar.</p>
                            </div>
                        </div>

                        <div class="rounded-3xl bg-white/10 p-8 shadow-xl shadow-indigo-900/20 backdrop-blur-lg">
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
