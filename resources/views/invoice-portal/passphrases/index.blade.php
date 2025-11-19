<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Passphrase Portal Invoice') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('passphrase_plain'))
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-6 text-amber-900">
                    <h3 class="text-lg font-semibold">Passphrase Baru</h3>
                    <p class="mt-2 text-sm">Simpan passphrase berikut dengan aman. Nilai ini hanya ditampilkan sekali.</p>
                    <div class="mt-4 flex flex-col gap-1">
                        <span class="text-sm font-medium text-amber-700">Tipe Akses</span>
                        <span class="text-base font-semibold">{{ session('passphrase_plain')['label'] ?? 'Tidak diketahui' }}</span>
                        <span class="text-sm font-mono tracking-wide bg-white/60 px-3 py-2 rounded border border-amber-200">{{ session('passphrase_plain')['value'] }}</span>
                    </div>
                </div>
            @endif

            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Buat Passphrase Baru</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Passphrase digunakan untuk mengizinkan akses pembuatan invoice publik dengan hak tertentu.</p>

                    <form action="{{ route('invoice-portal.passphrases.store') }}" method="POST" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                        @csrf
                        <div>
                            <label for="label" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Pemilik Passphrase</label>
                            <input type="text" name="label" id="label" value="{{ old('label') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: Ayu" required>
                            @error('label')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="access_type" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tipe Akses</label>
                            <select name="access_type" id="access_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">Pilih tipe akses</option>
                                @foreach ($accessTypes as $type)
                                    <option value="{{ $type->value }}" @selected(old('access_type') === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            @error('access_type')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="expires_at" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Kedaluwarsa (opsional)</label>
                            <input type="datetime-local" name="expires_at" id="expires_at" value="{{ old('expires_at') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('expires_at')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="passphrase" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Passphrase khusus (opsional)</label>
                            <input type="text" name="passphrase" id="passphrase" value="{{ old('passphrase') }}" placeholder="Biarkan kosong untuk generate otomatis" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('passphrase')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-4 flex justify-end">
                            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Simpan Passphrase</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar Passphrase Aktif</h3>
                        <x-sort-filter 
                            :sortBy="request('sort_by', 'created_at')"
                            :sortOrder="request('sort_order', 'desc')"
                            :sortOptions="[
                                'created_at' => 'Waktu Dibuat',
                                'updated_at' => 'Waktu Diupdate',
                                'expires_at' => 'Kedaluwarsa'
                            ]"
                            label=""
                        />
                    </div>

                    @if ($errors->hasBag('rotatePassphrase'))
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                            <h4 class="font-semibold text-sm">Gagal memperbarui passphrase:</h4>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                                @foreach ($errors->getBag('rotatePassphrase')->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @php
                        $editingPassphraseId = old('editing_passphrase');
                    @endphp

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Pemilik</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Tipe</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Kedaluwarsa</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Terakhir Dipakai</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Penggunaan</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Dibuat Oleh</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                @forelse ($passphrases as $passphrase)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                            <div class="font-semibold">{{ $passphrase->displayLabel() }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">ID: {{ $passphrase->public_id }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $passphrase->access_type->label() }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if (! $passphrase->is_active)
                                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Nonaktif</span>
                                            @elseif ($passphrase->isExpired())
                                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">Kedaluwarsa</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Aktif</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $passphrase->expires_at ? $passphrase->expires_at->timezone(config('app.timezone'))->format('d M Y H:i') : 'Tidak diatur' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $passphrase->last_used_at ? $passphrase->last_used_at->timezone(config('app.timezone'))->diffForHumans() : 'Belum pernah' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ number_format($passphrase->usage_count) }} kali
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $passphrase->creator?->name ?? 'Tidak diketahui' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            @php
                                                $isEditingThis = (string) $editingPassphraseId === (string) $passphrase->id;
                                                $defaultExpiresAt = optional($passphrase->expires_at)->timezone(config('app.timezone'))?->format('Y-m-d\TH:i');
                                                $labelValue = $isEditingThis ? old('label', $passphrase->label) : $passphrase->label;
                                                $expiresValue = $isEditingThis ? old('expires_at', $defaultExpiresAt) : $defaultExpiresAt;
                                                $customPassphraseValue = $isEditingThis ? old('passphrase') : '';
                                            @endphp
                                            <div class="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                    x-data="{}"
                                                    x-on:click.prevent="$dispatch('open-modal', 'edit-passphrase-{{ $passphrase->id }}')"
                                                >Edit</button>
                                                <form action="{{ route('invoice-portal.passphrases.deactivate', $passphrase) }}" method="POST" onsubmit="return confirm('Nonaktifkan passphrase ini? Akses terkait akan dicabut.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" @disabled(! $passphrase->is_active)>Nonaktifkan</button>
                                                </form>
                                            </div>
                                            @error('passphrase_'.$passphrase->id)
                                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                    </tr>
                                    <x-modal name="edit-passphrase-{{ $passphrase->id }}" :show="$isEditingThis || $errors->has('passphrase_'.$passphrase->id)" focusable class="p-6 sm:p-8 max-w-lg">
                                        <form action="{{ route('invoice-portal.passphrases.rotate', $passphrase) }}" method="POST" class="space-y-6">
                                            @csrf
                                            <input type="hidden" name="editing_passphrase" value="{{ $passphrase->id }}">

                                            <div class="space-y-1">
                                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Passphrase</h4>
                                                <p class="text-sm text-gray-600 dark:text-gray-300">Perbarui informasi passphrase atau putar nilai passphrase baru sesuai kebutuhan.</p>
                                            </div>

                                            @if ($isEditingThis && $errors->hasBag('rotatePassphrase'))
                                                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                                    <ul class="list-disc space-y-1 pl-5">
                                                        @foreach ($errors->getBag('rotatePassphrase')->all() as $message)
                                                            <li>{{ $message }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            @if ($errors->has('passphrase_'.$passphrase->id))
                                                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                                                    {{ $errors->first('passphrase_'.$passphrase->id) }}
                                                </div>
                                            @endif

                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <div class="space-y-2 md:col-span-2">
                                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Nama Pemilik</label>
                                                    <input type="text" name="label" value="{{ $labelValue }}" class="block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:bg-white focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" placeholder="Contoh: Ayu">
                                                </div>
                                                <div class="space-y-2">
                                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Kedaluwarsa baru</label>
                                                    <input type="datetime-local" name="expires_at" value="{{ $expiresValue }}" class="block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:bg-white focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                                </div>
                                                <div class="space-y-2">
                                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Passphrase khusus</label>
                                                    <input type="text" name="passphrase" value="{{ $customPassphraseValue }}" placeholder="Otomatis jika dikosongkan" class="block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:bg-white focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                                </div>
                                            </div>

                                            <div class="flex justify-end gap-2 mt-6">
                                                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                                                    Batal
                                                </x-secondary-button>
                                                <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                    Simpan Perubahan
                                                </button>
                                            </div>
                                        </form>
                                    </x-modal>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-300">Belum ada passphrase yang dibuat.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
