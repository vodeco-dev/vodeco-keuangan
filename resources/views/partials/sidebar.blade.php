<aside x-data="{
    sidebarOpen: (() => {
        const saved = localStorage.getItem('sidebarOpen');
        return saved !== null ? saved === 'true' : true;
    })(),
    init() {
        this.$watch('sidebarOpen', (value) => {
            localStorage.setItem('sidebarOpen', value);
        });
    }
}" class="relative flex h-screen flex-col bg-white text-gray-700 dark:bg-gray-900 dark:text-gray-200 p-4 overflow-y-auto transition-all duration-300 ease-in-out" :class="sidebarOpen ? 'w-64' : 'w-20'" style="overflow-x: visible;">

    {{-- Bagian Logo dan Nama Aplikasi --}}
    <div class="flex items-center mb-8 text-center self-center">
        <a href="{{ route('dashboard') }}">
            <div class="flex flex-col items-center justify-center w-full self-center px-8 transition-opacity duration-300 ease-in-out" 
                 :class="{'opacity-0 absolute pointer-events-none': !sidebarOpen, 'opacity-100': sidebarOpen}">
                <span class="text-2xl font-bold text-blue-900 dark:text-white">Finance App</span>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-300">By Vodeco</span>
            </div>
            <svg class="h-8 w-8 text-blue-900 dark:text-white transition-opacity duration-300 ease-in-out" 
                 :class="{ 'opacity-0 absolute pointer-events-none': sidebarOpen, 'opacity-100': !sidebarOpen }" 
                 fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15v-2h2v2h-2zm0-4v-6h2v6h-2z" />
            </svg>
        </a>
    </div>

    {{-- Tombol untuk Buka/Tutup Sidebar --}}
    <button @click="sidebarOpen = !sidebarOpen" class="absolute top-5 left-4 bg-white dark:bg-gray-800 p-1 rounded-full shadow-md transition-all duration-300 ease-in-out hover:scale-110">
        <svg class="h-6 w-6 transition-transform duration-300 ease-in-out" 
             :class="{ 'rotate-180': !sidebarOpen }"
             stroke="currentColor" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
    </button>

        {{-- Menu Navigasi Utama --}}
        <nav class="flex flex-col gap-2" style="overflow: visible;">
            @foreach ($navigation as $item)
            @php
            $isVisible = false;
            $userRole = auth()->user()->role->value;

            if (isset($item['roles'])) {
            $isVisible = in_array($userRole, (array) $item['roles'], true);
            } elseif (isset($item['can_not']) && $userRole != $item['can_not']) {
            $isVisible = true;
            } elseif (isset($item['can']) && $userRole == $item['can']) {
            $isVisible = true;
            } elseif (!isset($item['can']) && !isset($item['can_not'])) {
            $isVisible = true;
            }
            @endphp

            @if($isVisible)
            @if(isset($item['children']) && !empty($item['children']))
            @php
            $isActive = false;
            foreach ($item['children'] as $child) {
            if (request()->routeIs($child['route'])) {
            $isActive = true;
            break;
            }
            }
            @endphp
            <div x-data="{ open: {{ $isActive ? 'true' : 'false' }}, hoverOpen: false, menuPosition: { top: 0, left: 0 } }" 
                 @mouseenter="if (!sidebarOpen) { 
                     const rect = $el.getBoundingClientRect();
                     menuPosition = { top: rect.top, left: rect.right + 8 };
                     hoverOpen = true;
                 }" 
                 @mouseleave="setTimeout(() => hoverOpen = false, 200)"
                 class="relative z-50"
                 x-ref="menuItem">
                <button @click="if (sidebarOpen) open = !open"
                    class="flex items-center justify-between w-full px-3 py-2.5 rounded-lg transition-colors
                                       {{ $isActive
                                            ? 'bg-blue-700 text-white font-semibold shadow-lg'
                                            : 'hover:bg-gray-200 hover:text-gray-900 dark:text-white dark:hover:bg-gray-700 dark:hover:text-gray-100' }}">
                    <span class="flex items-center gap-3">
                        <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center" style="min-width: 24px; min-height: 24px;">
                            <span style="display: inline-flex; width: 24px; height: 24px;">
                                {!! $item['icon'] !!}
                            </span>
                        </span>
                        <span class="text-sm transition-opacity duration-300 ease-in-out whitespace-nowrap" 
                              :class="{ 'opacity-0 w-0 overflow-hidden': !sidebarOpen, 'opacity-100': sidebarOpen }">{{ $item['name'] }}</span>
                    </span>
                    <svg class="w-5 h-5 transition-all duration-300 ease-in-out" 
                         :class="{ 'rotate-180': open || hoverOpen, 'opacity-0 w-0 overflow-hidden': !sidebarOpen, 'opacity-100': sidebarOpen }" 
                         fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                {{-- Dropdown saat sidebar melebar --}}
                <div x-show="open && sidebarOpen" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="mt-1 pl-8 flex flex-col gap-1" 
                     style="display: none;">
                    @foreach ($item['children'] as $child)
                    <a href="{{ route($child['route']) }}"
                        class="px-3 py-2 rounded-lg text-sm transition-colors
                                          {{ request()->routeIs($child['route'])
                                                ? 'text-blue-700 font-bold'
                                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:text-white dark:hover:text-gray-100' }}">
                        {{ $child['name'] }}
                    </a>
                    @endforeach
                </div>
                {{-- Dropdown saat sidebar menyempit (hover) --}}
                <div x-show="hoverOpen && !sidebarOpen" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     @mouseenter="hoverOpen = true"
                     @mouseleave="hoverOpen = false"
                     :style="`position: fixed; top: ${menuPosition.top}px; left: ${menuPosition.left}px; z-index: 9999; display: none;`"
                     class="w-56 bg-white dark:bg-gray-800 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 py-2 flex flex-col gap-1"
                     style="display: none;">
                    @foreach ($item['children'] as $child)
                    <a href="{{ route($child['route']) }}"
                        class="px-4 py-2.5 text-sm transition-colors rounded-lg mx-1
                                          {{ request()->routeIs($child['route'])
                                                ? 'bg-blue-700 text-white font-semibold'
                                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        {{ $child['name'] }}
                    </a>
                    @endforeach
                </div>
            </div>
            @else
            <a href="{{ route($item['route']) }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                              {{ request()->routeIs($item['route'])
                                  ? 'bg-blue-700 text-white font-semibold shadow-lg'
                                  : 'hover:bg-gray-200 hover:text-gray-900 dark:text-white dark:hover:bg-gray-700 dark:hover:text-gray-100' }}">
                <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center" style="min-width: 24px; min-height: 24px;">
                    <span style="display: inline-flex; width: 24px; height: 24px;">
                        {!! $item['icon'] !!}
                    </span>
                </span>
                <span class="text-sm transition-opacity duration-300 ease-in-out whitespace-nowrap" 
                      :class="{ 'opacity-0 w-0 overflow-hidden': !sidebarOpen, 'opacity-100': sidebarOpen }">{{ $item['name'] }}</span>
            </a>
            @endif
            @endif
            @endforeach
        </nav>

        {{-- Menu Pengaturan di Bagian Bawah --}}
        <div class="mt-auto flex flex-col gap-2">
            <a href="{{ route('settings.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
                  {{ request()->routeIs('settings.index')
                      ? 'bg-blue-700 text-white font-semibold shadow-lg'
                      : 'hover:bg-gray-200 hover:text-gray-900 dark:text-white dark:hover:bg-gray-700 dark:hover:text-gray-100' }}">
                <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center" style="min-width: 24px; min-height: 24px;">
                    <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px; flex-shrink: 0;">
                        <path d="M128,80a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160Zm88-29.84q.06-2.16,0-4.32l14.92-18.64a8,8,0,0,0,1.48-7.06,107.21,107.21,0,0,0-10.88-26.25,8,8,0,0,0-6-3.93l-23.72-2.64q-1.48-1.56-3-3L186,40.54a8,8,0,0,0-3.94-6,107.71,107.71,0,0,0-26.25-10.87,8,8,0,0,0-7.06,1.49L130.16,40Q128,40,125.84,40L107.2,25.11a8,8,0,0,0-7.06-1.48A107.6,107.6,0,0,0,73.89,34.51a8,8,0,0,0-3.93,6L67.32,64.27q-1.56,1.49-3,3L40.54,70a8,8,0,0,0-6,3.94,107.71,107.71,0,0,0-10.87,26.25,8,8,0,0,0,1.49,7.06L40,125.84Q40,128,40,130.16L25.11,148.8a8,8,0,0,0-1.48,7.06,107.21,107.21,0,0,0,10.88,26.25,8,8,0,0,0,6,3.93l23.72,2.64q1.49,1.56,3,3L70,215.46a8,8,0,0,0,3.94,6,107.71,107.71,0,0,0,26.25,10.87,8,8,0,0,0,7.06-1.49L125.84,216q2.16.06,4.32,0l18.64,14.92a8,8,0,0,0,7.06,1.48,107.21,107.21,0,0,0,26.25-10.88,8,8,0,0,0,3.93-6l2.64-23.72q1.56-1.48,3-3L215.46,186a8,8,0,0,0,6-3.94,107.71,107.71,0,0,0,10.87-26.25,8,8,0,0,0-1.49-7.06Zm-16.1-6.5a73.93,73.93,0,0,1,0,8.68,8,8,0,0,0,1.74,5.48l14.19,17.73a91.57,91.57,0,0,1-6.23,15L187,173.11a8,8,0,0,0-5.1,2.64,74.11,74.11,0,0,1-6.14,6.14,8,8,0,0,0-2.64,5.1l-2.51,22.58a91.32,91.32,0,0,1-15,6.23l-17.74-14.19a8,8,0,0,0-5-1.75h-.48a73.93,73.93,0,0,1-8.68,0,8,8,0,0,0-5.48,1.74L100.45,215.8a91.57,91.57,0,0,1-15-6.23L82.89,187a8,8,0,0,0-2.64-5.1,74.11,74.11,0,0,1-6.14-6.14,8,8,0,0,0-5.1-2.64L46.43,170.6a91.32,91.32,0,0,1-6.23-15l14.19-17.74a8,8,0,0,0,1.74-5.48,73.93,73.93,0,0,1,0-8.68,8,8,0,0,0-1.74-5.48L40.2,100.45a91.57,91.57,0,0,1,6.23-15L69,82.89a8,8,0,0,0,5.1-2.64,74.11,74.11,0,0,1,6.14-6.14A8,8,0,0,0,82.89,69L85.4,46.43a91.32,91.32,0,0,1,15-6.23l17.74,14.19a8,8,0,0,0,5.48,1.74,73.93,73.93,0,0,1,8.68,0,8,8,0,0,0,5.48-1.74L155.55,40.2a91.57,91.57,0,0,1,15,6.23L173.11,69a8,8,0,0,0,2.64,5.1,74.11,74.11,0,0,1,6.14,6.14,8,8,0,0,0,5.1,2.64l22.58,2.51a91.32,91.32,0,0,1,6.23,15l-14.19,17.74A8,8,0,0,0,199.87,123.66Z"></path>
                    </svg>
                </span>
                <span class="text-sm transition-opacity duration-300 ease-in-out whitespace-nowrap" 
                      :class="{ 'opacity-0 w-0 overflow-hidden': !sidebarOpen, 'opacity-100': sidebarOpen }">Pengaturan</span>
            </a>
        </div>
</aside>
