<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $defaultTheme = \App\Models\Setting::get('theme', 'light');
        @endphp
        <meta name="default-theme" content="{{ $defaultTheme }}">

        <link rel="icon" href="{{ asset('favicon.png') }}">
        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased h-full">
        <div class="flex h-screen bg-gray-100 dark:bg-gray-900 overflow-hidden">
            
            <div class="flex-shrink-0 hidden md:block">
                @include('partials.sidebar')
            </div>

            <div class="flex-1 flex flex-col overflow-y-auto">
                @include('layouts.navigation')

                @if (isset($header))
                    <header class="bg-white shadow dark:bg-gray-800">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <main class="flex-1 p-6">
                    {{-- (FIX) Cek apakah $slot ada, jika tidak, gunakan @yield --}}
                    @if (isset($slot))
                        {{ $slot }}
                    @else
                        @yield('content')
                    @endif
                </main>
            </div>
        </div>
    @stack('scripts')
    </body>
</html>
