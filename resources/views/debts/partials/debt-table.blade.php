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
                        <div
                            class="relative inline-block text-left"
                            x-data="{
                                open: false,
                                menuStyle: '',
                                dropUp: false,
                                toggle() {
                                    this.open = !this.open;
                                },
                                close() {
                                    this.open = false;
                                },
                                handleWindowClick(event) {
                                    if (!this.open) return;
                                    const trigger = this.$refs.trigger;
                                    const menu = this.$refs.menu;
                                    if ((trigger && trigger.contains(event.target)) || (menu && menu.contains(event.target))) {
                                        return;
                                    }
                                    this.close();
                                },
                                updatePosition() {
                                    const trigger = this.$refs.trigger;
                                    const menu = this.$refs.menu;
                                    if (!trigger || !menu) {
                                        return;
                                    }

                                    const rect = trigger.getBoundingClientRect();
                                    const menuWidth = menu.offsetWidth;
                                    const menuHeight = menu.offsetHeight;
                                    const spacing = 8;
                                    const viewportLeft = window.scrollX + 16;
                                    const viewportRight = window.scrollX + window.innerWidth - 16;
                                    const viewportTop = window.scrollY + 16;
                                    const viewportBottom = window.scrollY + window.innerHeight - 16;

                                    let left = rect.right + window.scrollX - menuWidth;
                                    if (left < viewportLeft) {
                                        left = viewportLeft;
                                    }

                                    if (left + menuWidth > viewportRight) {
                                        left = Math.max(viewportLeft, viewportRight - menuWidth);
                                    }

                                    const spaceBelow = viewportBottom - rect.bottom - spacing;
                                    const spaceAbove = rect.top - viewportTop - spacing;

                                    this.dropUp = spaceBelow < menuHeight && spaceAbove > spaceBelow;

                                    let top;
                                    if (this.dropUp) {
                                        top = rect.top + window.scrollY - spacing - menuHeight;

                                        if (top < viewportTop) {
                                            this.dropUp = false;
                                            top = rect.bottom + window.scrollY + spacing;
                                        }
                                    }

                                    if (!this.dropUp) {
                                        top = rect.bottom + window.scrollY + spacing;

                                        if (top + menuHeight > viewportBottom) {
                                            top = Math.max(viewportTop, viewportBottom - menuHeight);
                                        }
                                    }

                                    this.menuStyle = `top: ${top}px; left: ${left}px;`;
                                }
                            }"
                            x-init="
                                $watch('open', value => {
                                    if (value) {
                                        $nextTick(() => updatePosition());
                                    }
                                });
                            "
                            @keydown.escape.window.stop="close()"
                            @resize.window="open && updatePosition()"
                            @scroll.window="open && updatePosition()"
                            @click.window="handleWindowClick($event)"
                        >
                            <button
                                type="button"
                                x-ref="trigger"
                                @click="toggle()"
                                class="inline-flex w-full justify-center rounded-full border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                <span class="sr-only">Buka menu aksi</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.75a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                </svg>
                            </button>

                            <template x-teleport="body">
                                <div
                                    x-cloak
                                    x-show="open"
                                    x-transition.origin.top.right
                                    x-ref="menu"
                                    :style="menuStyle"
                                    :class="dropUp ? 'origin-bottom-right' : 'origin-top-right'"
                                    class="fixed z-[9999] w-52 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                >
                                    <div class="py-1 text-left text-sm text-gray-700">
                                        @if ($debt->status == \App\Models\Debt::STATUS_BELUM_LUNAS)
                                            <button type="button" @click="open = false; openPaymentModal(@js($debt->toArray()))" class="block w-full px-4 py-2 text-left hover:bg-gray-100">Tambah Pembayaran</button>

                                            <a href="{{ route('debts.edit', $debt) }}" class="block px-4 py-2 hover:bg-gray-100" @click="open = false">Edit</a>

                                            <form action="{{ route('debts.fail', $debt) }}" method="POST" onsubmit="return confirm('Tandai catatan ini sebagai gagal project?');">
                                                @csrf
                                                <button type="submit" class="block w-full px-4 py-2 text-left text-red-600 hover:bg-gray-100" @click="open = false">Tandai Gagal</button>
                                            </form>
                                        @endif

                                        <button type="button" @click="open = false; detailModal = true; selectedDebt = @js($debt->toArray())" class="block w-full px-4 py-2 text-left hover:bg-gray-100">Lihat Riwayat</button>

                                        <form action="{{ route('debts.destroy', $debt) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus catatan ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="block w-full px-4 py-2 text-left text-red-600 hover:bg-gray-100" @click="open = false">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            </template>
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
