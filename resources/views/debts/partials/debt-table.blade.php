<div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead class="border-b">
            <tr>
                <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Deskripsi</th>
                <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Pihak Terkait</th>
                <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Tipe</th>
                <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Total</th>
                <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Dibayar</th>
                <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Sisa</th>
                <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Progres</th>
                <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Jatuh Tempo</th>
                <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-xs font-semibold tracking-wider text-gray-500 uppercase text-center">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($debts as $debt)
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $debt->description }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">{{ $debt->related_party }}</td>
                    <td class="px-4 py-3 text-sm">
                        @if ($debt->type == 'down_payment')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Down Payment</span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Invoices Iklan</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">Rp{{ number_format($debt->amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">Rp{{ number_format($debt->paid_amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">Rp{{ number_format($debt->remaining_amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="{{ $debt->type == 'down_payment' ? 'bg-blue-600' : 'bg-red-600' }} h-2.5 rounded-full" style="width: {{ $debt->progress }}%"></div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">{{ $debt->due_date ? \Carbon\Carbon::parse($debt->due_date)->isoFormat('D MMM YYYY') : '-' }}</td>
                    <td class="px-4 py-3 text-sm">
                        @if ($debt->status == \App\Models\Debt::STATUS_LUNAS)
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Lunas</span>
                        @elseif ($debt->status == \App\Models\Debt::STATUS_GAGAL)
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Gagal</span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800">Belum Lunas</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-center">
                        <div class="flex items-center justify-center gap-2">
                            @if ($debt->status == \App\Models\Debt::STATUS_BELUM_LUNAS)
                                <button @click='openPaymentModal(@js($debt->toArray()))' class="text-blue-600 hover:text-blue-900" title="Tambah Pembayaran">
                                    <svg fill="none" height="20" stroke="currentColor" viewBox="0 0 24 24" width="20">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </button>

                                <a href="{{ route('debts.edit', $debt) }}" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.3 4.8 2.9 2.9M7 7H4a1 1 0 0 0-1 1v10c0 .6.4 1 1 1h11c.6 0 1-.4 1-1v-4.5m2.4-10a2 2 0 0 1 0 3l-6.8 6.8L8 18l.7-3.6 6.9-6.8a2 2 0 0 1 2.8 0Z"/>
                                    </svg>
                                </a>

                                <form action="{{ route('debts.fail', $debt) }}" method="POST" class="inline" onsubmit="return confirm('Tandai catatan ini sebagai gagal project?');">
                                    @csrf
                                    <button type="submit" class="text-red-500 hover:text-red-800" title="Tandai Gagal">
                                        <svg fill="none" height="20" stroke="currentColor" viewBox="0 0 24 24" width="20">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.071 19h13.858c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.339 16c-.77 1.333.192 3 1.732 3Z"></path>
                                        </svg>
                                    </button>
                                </form>
                            @endif

                            {{-- Tombol Detail Riwayat --}}
                            <button @click='detailModal = true; selectedDebt = @js($debt->toArray())' class="text-gray-500 hover:text-gray-700" title="Lihat Riwayat">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>

                            <form action="{{ route('debts.destroy', $debt) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus catatan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-800" title="Hapus">
                                    <svg fill="none" height="20" stroke="currentColor" viewBox="0 0 24 24" width="20">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-6 text-center text-sm text-gray-500">{{ $emptyMessage ?? 'Belum ada catatan.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
