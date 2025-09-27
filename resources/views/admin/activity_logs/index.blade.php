<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Log Aktivitas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-white">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left">Waktu</th>
                                <th class="text-left">Pengguna</th>
                                <th class="text-left">Aksi</th>
                                <th class="text-left">IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr class="border-t">
                                    <td class="py-2">{{ $log->created_at }}</td>
                                    <td class="py-2">{{ $log->user->name ?? '-' }}</td>
                                    <td class="py-2">{{ $log->description }}</td>
                                    <td class="py-2">{{ $log->ip_address }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
