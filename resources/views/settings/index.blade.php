{{-- resources/views/settings/index.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf

                        <div class="mb-4">
                            <x-input-label for="app_name" :value="__('App Name')" />
                            <x-text-input
                                id="app_name"
                                name="app_name"
                                type="text"
                                class="mt-1 block w-full"
                                value="{{ old('app_name', $settings['app_name'] ?? '') }}"
                            />
                            <x-input-error :messages="$errors->get('app_name')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="currency" :value="__('Currency')" />
                            <x-text-input
                                id="currency"
                                name="currency"
                                type="text"
                                class="mt-1 block w-full"
                                value="{{ old('currency', $settings['currency'] ?? '') }}"
                            />
                            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="theme" :value="__('Theme')" />
                            <x-text-input
                                id="theme"
                                name="theme"
                                type="text"
                                class="mt-1 block w-full"
                                value="{{ old('theme', $settings['theme'] ?? '') }}"
                            />
                            <x-input-error :messages="$errors->get('theme')" class="mt-2" />
                        </div>

                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>