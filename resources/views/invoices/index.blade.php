<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Invoices') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ openModal: false, selectedInvoice: null }">
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
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ __('Customer Service') }}</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">{{ __('Total') }}</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">{{ __('Paid Amount') }}</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">{{ __('Remaining Amount') }}</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($invoices as $invoice)
                            <tr>
                                <td class="px-6 py-4">{{ $invoice->number }}</td>
                                <td class="px-6 py-4">{{ $invoice->status }}</td>
                                <td class="px-6 py-4">{{ $invoice->customerService?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-right">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">Rp {{ number_format($invoice->down_payment, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">Rp {{ number_format($invoice->total - $invoice->down_payment, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center items-center gap-2">
                                        <a href="{{ route('invoices.pdf', $invoice) }}" class="text-gray-600 hover:text-gray-900 dark:text-white" target="_blank">PDF</a>
                                        @if($invoice->public_token)
                                        <button type="button" class="text-purple-600 hover:text-purple-900" onclick="copyToClipboard('{{ route('invoices.public.show', ['token' => $invoice->public_token]) }}', this)">
                                            {{ __('Copy Link') }}
                                        </button>
                                        @endif
                                        @if($invoice->status === 'Draft')
                                        <form method="POST" action="{{ route('invoices.send', $invoice) }}">
                                            @csrf
                                            <button type="submit" class="text-blue-600 hover:text-blue-900">{{ __('Send') }}</button>
                                        </form>
                                        @endif
                                        @if($invoice->status !== 'Paid')
                                        <button type="button" @click="openModal = true; selectedInvoice = {{ $invoice->id }};" class="text-green-600 hover:text-green-900">{{ __('Record Payment') }}</button>
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

        <!-- Modal -->
        <div x-show="openModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="openModal" @click.away="openModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="openModal" @click.stop class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form :action="'/invoices/' + selectedInvoice + '/pay'" method="POST">
                        @csrf
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Record Payment
                            </h3>
                            <div class="mt-2">
                                <label for="payment_amount" class="block text-sm font-medium text-gray-700">Payment Amount</label>
                                <input type="number" name="payment_amount" id="payment_amount" step="0.01" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            <div class="mt-2">
                                <label for="payment_date" class="block text-sm font-medium text-gray-700">Payment Date</label>
                                <input type="date" name="payment_date" id="payment_date" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Save
                            </button>
                            <button @click="openModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
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