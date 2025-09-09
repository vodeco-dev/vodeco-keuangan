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
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">{{ __('Total') }}</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($invoices as $invoice)
                                <tr>
                                    <td class="px-6 py-4">{{ $invoice->number }}</td>
                                    <td class="px-6 py-4">{{ $invoice->status }}</td>
                                    <td class="px-6 py-4 text-right">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center items-center gap-2">
                                            <a href="{{ route('invoices.pdf', $invoice) }}" class="text-gray-600 hover:text-gray-900" target="_blank">PDF</a>
                                            <button type="button" class="text-purple-600 hover:text-purple-900" onclick="copyToClipboard('{{ route('invoices.public.show', ['token' => $invoice->public_token]) }}', this)">
                                                {{ __('Copy Link') }}
                                            </button>
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
    <script>
        function copyToClipboard(text, el) {
            // navigator.clipboard is available only in secure contexts (HTTPS)
            // As a fallback, we can use a temporary textarea element.
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    const originalText = el.innerText;
                    el.innerText = 'Copied!';
                    setTimeout(function() {
                        el.innerText = originalText;
                    }, 2000);
                }, function(err) {
                    alert('Could not copy text: ', err);
                });
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed'; // Prevent scrolling to bottom of page in MS Edge.
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                try {
                    document.execCommand('copy');
                    const originalText = el.innerText;
                    el.innerText = 'Copied!';
                    setTimeout(function() {
                        el.innerText = originalText;
                    }, 2000);
                } catch (err) {
                    alert('Fallback: Oops, unable to copy', err);
                }
                document.body.removeChild(textarea);
            }
        }
    </script>
</x-app-layout>
