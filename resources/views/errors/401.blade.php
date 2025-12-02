<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    
    <title>401 - Unauthorized | Finance Vodeco</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased h-full bg-gray-900" style="background-image: url('{{ asset('background-vodeco.jpg') }}'); background-size: cover; background-position: center;">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full text-center">
            <div class="mb-8">
                <h1 class="text-9xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400">
                    401
                </h1>
            </div>
            
            <div class="mb-6 flex justify-center">
                <svg class="w-24 h-24 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            
            <h2 class="text-3xl font-bold text-white mb-4">
                Unauthorized
            </h2>
            
            <div class="bg-white/10 backdrop-blur-2xl border border-white/30 rounded-2xl shadow-xl shadow-indigo-900/20 p-8 mb-8 text-left">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-white mb-3">
                        Apa yang terjadi?
                    </h3>
                    <p class="text-indigo-100 leading-relaxed">
                        Anda perlu login terlebih dahulu untuk mengakses halaman ini. Sesi Anda mungkin telah berakhir atau Anda belum melakukan autentikasi. Error ini bisa terjadi karena:
                    </p>
                    <ul class="mt-4 space-y-2 text-indigo-100 list-disc list-inside">
                        <li>Anda belum login ke aplikasi</li>
                        <li>Sesi login Anda telah kedaluwarsa</li>
                        <li>Token autentikasi tidak valid atau telah dihapus</li>
                        <li>Anda telah logout dari aplikasi</li>
                        <li>Cookie atau session browser telah dihapus</li>
                    </ul>
                </div>
                
                <div class="border-t border-white/20 pt-6">
                    <h3 class="text-lg font-semibold text-white mb-3">
                        Bagaimana cara mengatasi?
                    </h3>
                    <ol class="space-y-3 text-indigo-100">
                        <li class="flex items-start">
                            <span class="font-semibold text-blue-300 mr-2">1.</span>
                            <span><strong>Login ke aplikasi</strong> menggunakan email dan password yang terdaftar.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-blue-300 mr-2">2.</span>
                            <span><strong>Pastikan Anda menggunakan akun yang benar</strong> dan memiliki akses ke halaman yang diminta.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-blue-300 mr-2">3.</span>
                            <span><strong>Jangan tutup browser terlalu lama</strong> jika ingin tetap login. Sesi akan kedaluwarsa setelah beberapa waktu tidak aktif.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-blue-300 mr-2">4.</span>
                            <span><strong>Periksa pengaturan cookie browser</strong> Anda. Pastikan cookie diaktifkan untuk aplikasi ini.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-blue-300 mr-2">5.</span>
                            <span><strong>Hubungi administrator</strong> jika Anda yakin sudah login tetapi masih mendapat error ini.</span>
                        </li>
                    </ol>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('login') }}" 
                   class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Login
                </a>
                <a href="{{ url('/') }}" 
                   class="inline-flex items-center justify-center px-6 py-3 border border-white/30 text-base font-medium rounded-lg text-white bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 backdrop-blur-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Ke Halaman Utama
                </a>
            </div>
        </div>
    </div>
</body>
</html>

