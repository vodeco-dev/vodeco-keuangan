{{-- resources/views/debts/index.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Debts') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('debts.store') }}" class="mb-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <x-input-label for="creditor" :value="__('Creditor')" />
                                <x-text-input id="creditor" name="creditor" type="text" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('creditor')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="amount" :value="__('Amount')" />
                                <x-text-input id="amount" name="amount" type="number" step="0.01" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="due_date" :value="__('Due Date')" />
                                <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>
                        <div class="mt-4">
                            <x-primary-button>{{ __('Add Debt') }}</x-primary-button>
                        </div>
                    </form>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creditor</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($debts as $debt)
                                @php
                                    $dueClass = '';
                                    if ($debt->status !== 'paid') {
                                        if ($debt->due_date->isPast()) {
                                            $dueClass = 'text-red-600';
                                        } elseif ($debt->due_date->diffInDays(now()) <= 3) {
                                            $dueClass = 'text-yellow-600';
                                        }
                                    }
                                @endphp
                                <tr x-data="{ edit: false }">
                                    <td class="px-3 py-2">
                                        <span x-show="!edit">{{ $debt->creditor }}</span>
                                        <x-text-input x-show="edit" name="creditor" class="w-full" x-ref="creditor" value="{{ $debt->creditor }}" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <span x-show="!edit">{{ number_format($debt->amount, 2) }}</span>
                                        <x-text-input x-show="edit" name="amount" type="number" step="0.01" class="w-full" x-ref="amount" value="{{ $debt->amount }}" />
                                    </td>
                                    <td class="px-3 py-2 {{ $dueClass }}">
                                        <span x-show="!edit">{{ $debt->due_date->format('Y-m-d') }}</span>
                                        <x-text-input x-show="edit" name="due_date" type="date" class="w-full" x-ref="due_date" value="{{ $debt->due_date->format('Y-m-d') }}" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <span x-show="!edit" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $debt->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">{{ ucfirst($debt->status) }}</span>
                                        <select x-show="edit" name="status" x-ref="status" class="rounded-md border-gray-300 w-full">
                                            <option value="pending" @selected($debt->status === 'pending')>Pending</option>
                                            <option value="paid" @selected($debt->status === 'paid')>Paid</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 text-right space-x-2">
                                        <div x-show="!edit" class="space-x-2">
                                            <x-secondary-button x-on:click.prevent="edit = true">{{ __('Edit') }}</x-secondary-button>
                                            <form method="POST" action="{{ route('debts.destroy', $debt) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <x-danger-button>{{ __('Delete') }}</x-danger-button>
                                            </form>
                                        </div>
                                        <div x-show="edit" class="space-x-2">
                                            <form method="POST" action="{{ route('debts.update', $debt) }}" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="creditor" x-bind:value="$refs.creditor.value" />
                                                <input type="hidden" name="amount" x-bind:value="$refs.amount.value" />
                                                <input type="hidden" name="due_date" x-bind:value="$refs.due_date.value" />
                                                <input type="hidden" name="status" x-bind:value="$refs.status.value" />
                                                <x-primary-button>{{ __('Save') }}</x-primary-button>
                                            </form>
                                            <x-secondary-button x-on:click.prevent="edit = false">{{ __('Cancel') }}</x-secondary-button>
                                        </div>
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

