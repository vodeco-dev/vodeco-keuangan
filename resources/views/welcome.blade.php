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
    <body class="antialiased animated-gradient-bg">
        <div class="relative sm:flex sm:justify-center sm:items-center min-h-screen selection:bg-red-500 selection:text-white">
            @if (Route::has('login'))
                <div class="sm:fixed sm:top-0 sm:right-0 p-6 text-right z-10">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="font-semibold text-gray-200 hover:text-white">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="font-semibold text-gray-200 hover:text-white">Log in</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="ml-4 font-semibold text-gray-200 hover:text-white">Register</a>
                        @endif
                    @endauth
                </div>
            @endif

            <div class="max-w-7xl mx-auto p-6 lg:p-8">
                <div class="flex flex-col items-center justify-center text-center">
                    {{-- === ANIMASI STATIS "SELAMAT DATANG" === --}}
                    <div class="typing-container">
                        {{-- Warna teks diubah menjadi putih agar kontras dengan background --}}
                        <h1 class="typing-text text-4xl lg:text-6xl font-extrabold text-white">
                            Selamat Datang di Vodeco
                        </h1>
                    </div>

                    {{-- === ANIMASI DINAMIS (Ketik-Hapus) === --}}
                    <p class="mt-4 text-lg lg:text-2xl text-gray-300 h-8">
                        <span id="dynamic-text" class="font-semibold"></span>
                        <span class="blinking-cursor">|</span>
                    </p>
                </div>
            </div>

        </div>

        {{-- === CSS UNTUK SEMUA ANIMASI DAN BACKGROUND === --}}
        <style>
            .animated-gradient-bg {
                background: linear-gradient(-45deg, #000080, #4b0082, #000033, #2e0854);
                background-size: 400% 400%;
                animation: gradientAnimation 15s ease infinite;
                color: white; /* Mengatur warna teks default menjadi putih */
            }

            @keyframes gradientAnimation {
                0% {
                    background-position: 0% 50%;
                }
                50% {
                    background-position: 100% 50%;
                }
                100% {
                    background-position: 0% 50%;
                }
            }

            .typing-container {
                display: inline-block;
            }

            .typing-text {
                width: 0;
                overflow: hidden;
                white-space: nowrap;
                border-right: .15em solid orange;
                animation: typing 3.5s steps(25, end) forwards, blink-caret-static .75s step-end infinite;
            }

            @keyframes typing {
                from { width: 0 }
                to { width: 100% }
            }
            
            @keyframes blink-caret-static {
                from, to { border-color: transparent }
                50% { border-color: orange; }
            }

            .blinking-cursor {
                font-weight: 500;
                color: #D1D5DB; /* gray-300 */
                animation: blink-dynamic 1s step-end infinite;
            }

            @keyframes blink-dynamic {
                from, to { color: transparent; }
                50% { color: #D1D5DB; } /* gray-300 */
            }
        </style>

        {{-- === JAVASCRIPT UNTUK ANIMASI KETIK-HAPUS === --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const dynamicText = document.getElementById('dynamic-text');
                const words = [
                    "Web Development",
                    "Corporate Branding",
                    "SEO Specialist",
                    "UI/UX Design",
                    "Digital Campaign",
                    "Social Media Management"
                ];
                
                let wordIndex = 0;
                let charIndex = 0;
                let isDeleting = false;
                let delay = 2000;

                function type() {
                    const currentWord = words[wordIndex];
                    let speed = isDeleting ? 75 : 150;

                    dynamicText.textContent = currentWord.substring(0, charIndex);

                    if (!isDeleting && charIndex < currentWord.length) {
                        charIndex++;
                        delay = 150;
                    } else if (isDeleting && charIndex > 0) {
                        charIndex--;
                        delay = 75;
                    } else {
                        isDeleting = !isDeleting;
                        if (isDeleting) {
                            delay = 2000;
                        } else {
                            wordIndex = (wordIndex + 1) % words.length;
                            delay = 500;
                        }
                    }

                    setTimeout(type, delay);
                }

                type();
            });
        </script>
    </body>
</html>