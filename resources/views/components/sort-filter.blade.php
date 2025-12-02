@props([
    'sortBy' => 'created_at',
    'sortOrder' => 'desc',
    'sortOptions' => [
        'created_at' => 'Waktu Dibuat',
        'updated_at' => 'Waktu Diupdate',
    ],
    'label' => 'Urutkan:',
])

<div class="flex items-center gap-2">
    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    <form method="GET" action="{{ request()->url() }}" class="flex items-center gap-2">
        @foreach(request()->except(['sort_by', 'sort_order', 'page']) as $key => $value)
            @if(is_array($value))
                @foreach($value as $v)
                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        
        <select name="sort_by" onchange="this.form.submit()" 
            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            @foreach($sortOptions as $value => $label)
                <option value="{{ $value }}" {{ $sortBy === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        
        <select name="sort_order" onchange="this.form.submit()" 
            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="desc" {{ $sortOrder === 'desc' ? 'selected' : '' }}>Terbaru → Terlama</option>
            <option value="asc" {{ $sortOrder === 'asc' ? 'selected' : '' }}>Terlama → Terbaru</option>
        </select>
    </form>
</div>

