<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Proyek') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 text-right">
                <a href="{{ route('projects.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Tambah Proyek</a>
            </div>
            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-4 mb-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 text-red-700 p-4 mb-4">{{ session('error') }}</div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <table class="w-full text-left">
                    <thead class="border-b">
                        <tr>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Nama</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Klien</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($projects as $project)
                            <tr>
                                <td class="px-6 py-4">{{ $project->name }}</td>
                                <td class="px-6 py-4">{{ $project->client->name }}</td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('projects.edit', $project) }}" class="text-blue-600 mr-2">Edit</a>
                                    <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-4">
                    {{ $projects->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
