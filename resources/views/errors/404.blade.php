<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    
    <title>404 - Halaman Tidak Ditemukan | Finance Vodeco</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased h-full bg-gray-900" style="background-image: url('{{ asset('background-vodeco.jpg') }}'); background-size: cover; background-position: center;">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full text-center">
            <div class="mb-8">
                <h1 class="text-9xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-indigo-400">
                    404
                </h1>
            </div>
            
            <div class="mb-6 flex justify-center">
                <svg class="w-24 h-24 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            
            <h2 class="text-3xl font-bold text-white mb-4">
                Halaman Tidak Ditemukan
            </h2>
            
            <div class="bg-white/10 backdrop-blur-2xl border border-white/30 rounded-2xl shadow-xl shadow-indigo-900/20 p-8 mb-8 text-left">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-white mb-3">
                        Apa yang terjadi?
                    </h3>
                    <p class="text-indigo-100 leading-relaxed">
                        Halaman yang Anda cari tidak dapat ditemukan. Ini bisa terjadi karena:
                    </p>
                    <ul class="mt-4 space-y-2 text-indigo-100 list-disc list-inside">
                        <li>URL yang Anda masukkan salah atau tidak lengkap</li>
                        <li>Halaman telah dipindahkan atau dihapus</li>
                        <li>Link yang Anda klik sudah tidak valid</li>
                        <li>Anda tidak memiliki akses ke halaman tersebut</li>
                    </ul>
                </div>
                
                <div class="border-t border-white/20 pt-6">
                    <h3 class="text-lg font-semibold text-white mb-3">
                        Bagaimana cara mengatasi?
                    </h3>
                    <ol class="space-y-3 text-indigo-100">
                        <li class="flex items-start">
                            <span class="font-semibold text-purple-300 mr-2">1.</span>
                            <span>Periksa kembali URL yang Anda masukkan. Pastikan tidak ada typo atau karakter yang salah.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-purple-300 mr-2">2.</span>
                            <span>Kembali ke halaman sebelumnya menggunakan tombol browser atau klik tombol di bawah.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-purple-300 mr-2">3.</span>
                            <span>Gunakan menu navigasi untuk mencari halaman yang Anda inginkan.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-purple-300 mr-2">4.</span>
                            <span>Jika masalah berlanjut, hubungi administrator sistem.</span>
                        </li>
                    </ol>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url()->previous() !== request()->url() ? url()->previous() : route('dashboard') }}" 
                class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </a>
                <a href="{{ route('dashboard') }}" 
                class="inline-flex items-center justify-center px-6 py-3 border border-white/30 text-base font-medium rounded-lg text-white bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200 backdrop-blur-sm">
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

