{{-- Menggunakan warna latar belakang terang untuk tampilan modern --}}
<aside class="flex h-screen flex-col w-64 bg-gray-100 text-gray-700 p-4">
  
  {{-- Bagian Logo dan Nama Aplikasi --}}
  <div class="flex items-center mb-8 text-center">
    {{-- Memanggil komponen logo yang sudah Anda buat sebelumnya --}}
    <a href="{{ route('dashboard') }}">
      <span class="text-2xl font-bold text-gray-900 text-center">Vodeco</span>
    </a>
    
  </div>

  {{-- Menu Navigasi Utama --}}
  <nav class="flex flex-col gap-2">
    {{-- Setiap link akan memiliki ikon dan indikator aktif di sebelah kiri --}}
    
    <a href="{{ route('dashboard') }}" 
       class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
              {{ request()->routeIs('dashboard') 
                 ? 'bg-purple-700 text-white font-semibold shadow-lg'
                 : 'hover:bg-gray-200 hover:text-gray-900' }}">
      {{-- Ikon Dashboard (Home) --}}
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" /></svg>
      <span class="text-sm">Dashboard</span>
    </a>

    <a href="{{ route('transactions.index') }}" 
       class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
              {{ request()->routeIs('transactions.index') 
                 ? 'bg-purple-700 text-white font-semibold shadow-lg'
                 : 'hover:bg-gray-200 hover:text-gray-900' }}">
      {{-- Ikon Transaksi (Arrows Right Left) --}}
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
      <span class="text-sm">Transaksi</span>
    </a>

    <a href="{{ route('categories.index') }}" 
       class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
              {{ request()->routeIs('categories.index') 
                 ? 'bg-purple-700 text-white font-semibold shadow-lg'
                 : 'hover:bg-gray-200 hover:text-gray-900' }}">
      {{-- Ikon Kategori (Tag) --}}
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" /></svg>
      <span class="text-sm">Kategori</span>
    </a>

    <a href="{{ route('reports') }}" 
       class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
              {{ request()->routeIs('reports') 
                 ? 'bg-purple-700 text-white font-semibold shadow-lg'
                 : 'hover:bg-gray-200 hover:text-gray-900' }}">
      {{-- Ikon Laporan (Chart Pie) --}}
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 100 15 7.5 7.5 0 000-15z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
      <span class="text-sm">Laporan</span>
    </a>

    <a href="{{ route('debts') }}" 
       class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
              {{ request()->routeIs('debts') 
                 ? 'bg-purple-700 text-white font-semibold shadow-lg'
                 : 'hover:bg-gray-200 hover:text-gray-900' }}">
      {{-- Ikon Hutang (Receipt Percent) --}}
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-1.5h5.25m-5.25 0h3m-3 0h-1.5m2.14-9a2.25 2.25 0 01-2.14 0m2.14 0a2.25 2.25 0 00-2.14 0" /></svg>
      <span class="text-sm">Hutang</span>
    </a>
  </nav>

  {{-- Menu Pengaturan di Bagian Bawah --}}
  <div class="mt-auto flex flex-col gap-2">
    <a href="{{ route('settings') }}" 
       class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
              {{ request()->routeIs('settings') 
                 ? 'bg-purple-700 text-white font-semibold shadow-lg'
                 : 'hover:bg-gray-200 hover:text-gray-900' }}">
      {{-- Ikon Pengaturan (Cog) --}}
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21m-1.5 0H12m-8.457 3.077l1.41-.513m14.095-5.13l-1.41-.513M5.106 17.785l1.153-.964M17.74 5.106l-1.152.964m-4.695 6.368a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" /></svg>
      <span class="text-sm">Pengaturan</span>
    </a>
  </div>
</aside>