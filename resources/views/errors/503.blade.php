<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    
    <title>503 - Layanan Tidak Tersedia | Finance Vodeco</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased h-full bg-gray-900" style="background-image: url('{{ asset('background-vodeco.jpg') }}'); background-size: cover; background-position: center;">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full text-center">
            <!-- Error Code -->
            <div class="mb-8">
                <h1 class="text-9xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">
                    503
                </h1>
            </div>
            
            <!-- Error Icon -->
            <div class="mb-6 flex justify-center">
                <svg class="w-24 h-24 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            
            <!-- Error Title -->
            <h2 class="text-3xl font-bold text-white mb-4">
                Layanan Tidak Tersedia
            </h2>
            
            <!-- Error Description -->
            <div class="bg-white/10 backdrop-blur-2xl border border-white/30 rounded-2xl shadow-xl shadow-indigo-900/20 p-8 mb-8 text-left">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-white mb-3">
                        Apa yang terjadi?
                    </h3>
                    <p class="text-indigo-100 leading-relaxed">
                        Server sedang dalam maintenance atau tidak dapat menangani permintaan saat ini. Layanan akan kembali normal dalam waktu singkat. Error ini bisa terjadi karena:
                    </p>
                    <ul class="mt-4 space-y-2 text-indigo-100 list-disc list-inside">
                        <li>Server sedang dalam maintenance atau update</li>
                        <li>Server kelebihan beban dan tidak dapat menangani permintaan baru</li>
                        <li>Database atau service pendukung sedang tidak tersedia</li>
                        <li>Konfigurasi server sedang diubah</li>
                        <li>Masalah sementara dengan infrastruktur</li>
                    </ul>
                </div>
                
                <div class="border-t border-white/20 pt-6">
                    <h3 class="text-lg font-semibold text-white mb-3">
                        Bagaimana cara mengatasi?
                    </h3>
                    <ol class="space-y-3 text-indigo-100">
                        <li class="flex items-start">
                            <span class="font-semibold text-indigo-300 mr-2">1.</span>
                            <span><strong>Tunggu beberapa menit</strong> dan coba lagi. Maintenance biasanya tidak berlangsung lama.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-indigo-300 mr-2">2.</span>
                            <span><strong>Refresh halaman</strong> secara berkala untuk mengecek apakah layanan sudah kembali normal.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-indigo-300 mr-2">3.</span>
                            <span><strong>Periksa status layanan</strong> jika tersedia. Administrator biasanya memberikan informasi tentang maintenance.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-indigo-300 mr-2">4.</span>
                            <span><strong>Hapus cache browser</strong> dan coba akses lagi setelah beberapa saat.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-semibold text-indigo-300 mr-2">5.</span>
                            <span><strong>Hubungi administrator</strong> jika masalah berlanjut lebih dari 30 menit atau jika Anda memiliki kebutuhan mendesak.</span>
                        </li>
                    </ol>
                </div>
                
                <div class="border-t border-white/20 pt-6 mt-6">
                    <div class="bg-blue-500/20 border border-blue-400/30 rounded-lg p-4">
                        <p class="text-sm text-blue-100">
                            <strong>Informasi:</strong> Maintenance biasanya dilakukan untuk meningkatkan performa dan keamanan aplikasi. Kami berusaha meminimalkan gangguan dan akan kembali online secepat mungkin.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button onclick="window.location.reload()" 
                   class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Coba Lagi
                </button>
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center justify-center px-6 py-3 border border-white/30 text-base font-medium rounded-lg text-white bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 backdrop-blur-sm">
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

