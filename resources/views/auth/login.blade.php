<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4 text-sm font-medium text-emerald-200" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="space-y-2">
            <x-input-label for="email" :value="__('Nama Pengguna atau Alamat Email')" class="text-sm font-semibold text-indigo-100" />
            <x-text-input id="email" class="block mt-1 w-full border-transparent bg-white/90 text-gray-900 placeholder-indigo-200 focus:border-indigo-400 focus:ring-indigo-300" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-rose-200" />
        </div>

        <!-- Password -->
        <div class="mt-6 space-y-2">
            <x-input-label for="password" :value="__('Password')" class="text-sm font-semibold text-indigo-100" />

            <x-text-input id="password" class="block mt-1 w-full border-transparent bg-white/90 text-gray-900 placeholder-indigo-200 focus:border-indigo-400 focus:ring-indigo-300"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-rose-200" />
        </div>

        <!-- Remember Me -->
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

            <x-primary-button class="inline-flex justify-center rounded-full bg-gradient-to-r from-indigo-400 to-violet-500 px-6 py-3 text-sm font-semibold uppercase tracking-widest text-white shadow-lg shadow-indigo-900/40 transition hover:from-indigo-300 hover:to-violet-400 focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:ring-offset-2 focus:ring-offset-indigo-900">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
