<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pendapatan Berulang') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <form method="POST" action="{{ route('recurring_revenues.store') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Client</label>
                        <select name="client_id" class="w-full border rounded-md" required>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kategori</label>
                        <select name="category_id" class="w-full border rounded-md">
                            <option value="">-</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">User</label>
                        <input type="number" name="user_id" class="w-full border rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="number" step="0.01" name="amount" class="w-full border rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Frequency</label>
                        <select name="frequency" class="w-full border rounded-md" required>
                            <option value="monthly">Bulanan</option>
                            <option value="weekly">Mingguan</option>
                            <option value="daily">Harian</option>
                            <option value="yearly">Tahunan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Next Run</label>
                        <input type="date" name="next_run" class="w-full border rounded-md" required>
                    </div>
                    <div class="md:col-span-6 text-right">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Tambah</button>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="w-full text-left">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Client</th>
                            <th class="px-4 py-2">Amount</th>
                            <th class="px-4 py-2">Frequency</th>
                            <th class="px-4 py-2">Next Run</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($revenues as $rev)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $rev->client->name }}</td>
                                <td class="px-4 py-2">{{ number_format($rev->amount,2) }}</td>
                                <td class="px-4 py-2">{{ $rev->frequency }}</td>
                                <td class="px-4 py-2">{{ $rev->next_run->toDateString() }}</td>
                                <td class="px-4 py-2">{{ $rev->paused ? 'Paused' : 'Active' }}</td>
                                <td class="px-4 py-2">
                                    @if($rev->paused)
                                        <form action="{{ route('recurring_revenues.resume', $rev) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="text-green-600">Resume</button>
                                        </form>
                                    @else
                                        <form action="{{ route('recurring_revenues.pause', $rev) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="text-yellow-600">Pause</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('recurring_revenues.destroy', $rev) }}" method="POST" class="inline" onsubmit="return confirm('Hapus?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 ml-2">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
