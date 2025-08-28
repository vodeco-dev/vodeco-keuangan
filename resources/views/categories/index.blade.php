@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-bold mb-4">{{ __('Categories') }}</h1>

    <form method="POST" action="{{ route('categories.store') }}" class="mb-6">
        @csrf
        <div class="mb-4">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
        </div>
        <div class="mb-4">
            <x-input-label for="type" :value="__('Type')" />
            <x-text-input id="type" name="type" type="text" class="mt-1 block w-full" required />
        </div>
        <x-primary-button>{{ __('Add Category') }}</x-primary-button>
    </form>

    <table class="min-w-full bg-white border border-gray-200">
        <thead>
            <tr>
                <th class="px-4 py-2 border-b text-left">{{ __('Name') }}</th>
                <th class="px-4 py-2 border-b text-left">{{ __('Type') }}</th>
                <th class="px-4 py-2 border-b text-left">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
                <tr>
                    <td class="px-4 py-2 border-b">
                        <x-text-input form="update-{{ $category->id }}" name="name" value="{{ $category->name }}" class="w-full" />
                    </td>
                    <td class="px-4 py-2 border-b">
                        <x-text-input form="update-{{ $category->id }}" name="type" value="{{ $category->type }}" class="w-full" />
                    </td>
                    <td class="px-4 py-2 border-b">
                        <form id="update-{{ $category->id }}" method="POST" action="{{ route('categories.update', $category) }}" class="inline">
                            @csrf
                            @method('PUT')
                            <x-primary-button>{{ __('Edit') }}</x-primary-button>
                        </form>
                        <form method="POST" action="{{ route('categories.destroy', $category) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <x-primary-button class="ml-2 bg-red-500 hover:bg-red-600">{{ __('Delete') }}</x-primary-button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
