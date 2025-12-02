<x-guest-layout>
    <x-auth-session-status class="mb-4 text-sm font-medium text-emerald-200" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="space-y-2">
            <x-input-label for="email" :value="__('Nama Pengguna atau Alamat Email')" class="text-sm font-semibold text-indigo-100" />
            <x-text-input id="email" class="block mt-1 w-full border-transparent bg-white/90 text-gray-900 placeholder-indigo-200 focus:border-indigo-400 focus:ring-indigo-300" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-rose-200" />
        </div>

        <div class="mt-6 space-y-2">
            <x-input-label for="password" :value="__('Password')" class="text-sm font-semibold text-indigo-100" />

            <div class="relative">
                <x-text-input id="password" class="block mt-1 w-full border-transparent bg-white/90 text-gray-900 placeholder-indigo-200 focus:border-indigo-400 focus:ring-indigo-300"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                    <svg class="h-6 w-6 text-gray-700 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="togglePassword">
                        <path class="eye-open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path class="eye-open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        <path class="eye-closed hidden"
  fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
  d="M2.5 12C4.9 7.5 8.9 5 12 5s7.1 2.5 9.5 7c-2.4 4.5-6.4 7-9.5 7s-7.1-2.5-9.5-7z
     M12 9a3 3 0 1 1 0 6a3 3 0 0 1 0-6z
     M3 3L21 21" />
                    </svg>
                </div>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-rose-200" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-transparent bg-white/20 text-indigo-200 shadow-sm focus:ring-indigo-300" name="remember">
                <span class="ms-2 text-sm text-indigo-100">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-indigo-100 underline decoration-indigo-300/50 underline-offset-4 transition hover:text-white" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="inline-flex justify-center rounded-full bg-blue-600 px-6 py-3 text-sm font-semibold uppercase tracking-widest text-white shadow-lg shadow-indigo-900/40 transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:ring-offset-2 focus:ring-offset-indigo-900">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
