@extends('layouts.app')

@section('content')
    <h2 class="text-3xl font-bold text-gray-800 mb-8">Preferensi Tampilan</h2>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('settings.display.update') }}" method="POST" class="space-y-6 max-w-md">
        @csrf
        <div>
            <label class="block text-gray-700 dark:text-gray-300 mb-2">Tema</label>
            <div class="flex items-center space-x-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="theme" value="light" @checked(old('theme', $theme) === 'light') class="form-radio">
                    <span class="ml-2">Terang</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="theme" value="dark" @checked(old('theme', $theme) === 'dark') class="form-radio">
                    <span class="ml-2">Gelap</span>
                </label>
            </div>
            @error('theme')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
    </form>
@endsection
