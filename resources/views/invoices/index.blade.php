<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Invoices') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-4">
                    <a href="{{ route('invoices.create') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">{{ __('New Invoice') }}</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="border-b">
                            <tr>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ __('Number') }}</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ __('Client') }}</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">{{ __('Total') }}</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($invoices as $invoice)
                                <tr>
                                    <td class="px-6 py-4">{{ $invoice->number }}</td>
                                    <td class="px-6 py-4">{{ $invoice->client?->name }}</td>
                                    <td class="px-6 py-4">{{ $invoice->status }}</td>
                                    <td class="px-6 py-4 text-right">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center items-center gap-2">
                                            @if($invoice->status === 'Draft')
                                                <form method="POST" action="{{ route('invoices.send', $invoice) }}">
                                                    @csrf
                                                    <button type="submit" class="text-blue-600 hover:text-blue-900">{{ __('Send') }}</button>
                                                </form>
                                            @endif
                                            @if($invoice->status !== 'Paid')
                                                <form method="POST" action="{{ route('invoices.pay', $invoice) }}">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-900">{{ __('Mark Paid') }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $invoices->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
