<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Invoice') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('invoices.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Client') }}</label>
                        <select name="client_id" class="w-full border rounded-lg px-3 py-2">
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Project') }}</label>
                        <select name="project_id" class="w-full border rounded-lg px-3 py-2">
                            <option value="">-</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Number') }}</label>
                        <input type="text" name="number" class="w-full border rounded-lg px-3 py-2" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Due Date') }}</label>
                        <input type="date" name="due_date" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Item Description') }}</label>
                        <input type="text" name="items[0][description]" class="w-full border rounded-lg px-3 py-2" required>
                    </div>
                    <div class="mb-4 flex gap-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Quantity') }}</label>
                            <input type="number" name="items[0][quantity]" class="w-full border rounded-lg px-3 py-2" value="1" min="1" required>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Price') }}</label>
                            <input type="number" step="0.01" name="items[0][price]" class="w-full border rounded-lg px-3 py-2" required>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
