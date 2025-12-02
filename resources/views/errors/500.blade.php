<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    
    <title>500 - Kesalahan Server | Finance Vodeco</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased h-full bg-gray-900" style="background-image: url('{{ asset('background-vodeco.jpg') }}'); background-size: cover; background-position: center;">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full text-center">
            <div class="mb-8">
                <h1 class="text-9xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-pink-400">
                    500
                </h1>
            </div>
            
            <div class="mb-6 flex justify-center">
                <svg class="w-24 h-24 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            
            <h2 class="text-3xl font-bold text-white mb-4">
                Kesalahan Server Internal
            </h2>
            
            <div class="bg-white/10 backdrop-blur-2xl border border-white/30 rounded-2xl shadow-xl shadow-indigo-900/20 p-8 mb-8 text-left">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-white mb-3">
                        Apa yang terjadi?
                    </h3>
                    <p class="text-indigo-100 leading-relaxed">
                        Terjadi kesalahan pada server saat memproses permintaan Anda. Ini adalah masalah di sisi server, bukan dari perangkat atau koneksi Anda. Error ini bisa terjadi karena:
                    </p>
                    <ul class="mt-4 space-y-2 text-indigo-100 list-disc list-inside">
                        <li>Masalah pada kode aplikasi atau konfigurasi server</li>
                        <li>Database sedang bermasalah atau tidak dapat diakses</li>
                        <li>Server kelebihan beban atau sedang maintenance</li>
                        <li>Masalah dengan file atau direktori yang diperlukan</li>
                        <li>Kesalahan dalam memproses data yang Anda kirim</li>
                    </ul>
                </div>
                
                <div class="border-t border-white/20 pt-6">
                    <h3 class="text-lg font-semibold text-white mb-3">
                        Bagaimana cara mengatasi?
                    </h3>
                    <ol class="space-y-3 text-indigo-100">
                        <li class="flex items-start">
                            <span class="font-semibold text-red-300 mr-2">1.</span>
                            <span><strong>Tunggu beberapa saat</strong> dan coba lagi. Masalah mungkin bersifat sementara.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-red-300 mr-2">2.</span>
                            <span><strong>Refresh halaman</strong> atau coba akses dari halaman lain terlebih dahulu.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-red-300 mr-2">3.</span>
                            <span><strong>Hapus cache browser</strong> dan cookie, lalu coba lagi.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-red-300 mr-2">4.</span>
                            <span><strong>Periksa koneksi internet</strong> Anda. Pastikan koneksi stabil.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-red-300 mr-2">5.</span>
                            <span><strong>Hubungi administrator</strong> jika masalah berlanjut. Berikan informasi tentang apa yang Anda lakukan saat error terjadi.</span>
                        </li>
                    </ol>
                </div>
                
                <div class="border-t border-white/20 pt-6 mt-6">
                    <div class="bg-yellow-500/20 border border-yellow-400/30 rounded-lg p-4">
                        <p class="text-sm text-yellow-100">
                            <strong>Catatan:</strong> Jika Anda adalah administrator sistem, periksa file log di <code class="bg-yellow-500/30 px-2 py-1 rounded">storage/logs/laravel.log</code> untuk detail kesalahan.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button onclick="window.location.reload()" 
                class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Coba Lagi
                </button>
                <a href="{{ route('dashboard') }}" 
                class="inline-flex items-center justify-center px-6 py-3 border border-white/30 text-base font-medium rounded-lg text-white bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 backdrop-blur-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Ke Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>

