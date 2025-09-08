<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Permintaan Penghapusan Transaksi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left">Transaksi</th>
                                <th class="text-left">Diminta Oleh</th>
                                <th class="text-left">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $request)
                                <tr class="border-t">
                                    <td class="py-2">#{{ $request->transaction_id }}</td>
                                    <td class="py-2">{{ $request->requester->name }}</td>
                                    <td class="py-2">
                                        <form method="POST" action="{{ route('admin.deletion-requests.approve', $request) }}" class="inline">
                                            @csrf
                                            <button class="text-green-600 mr-2">Setujui</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.deletion-requests.reject', $request) }}" class="inline">
                                            @csrf
                                            <button class="text-red-600">Tolak</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
