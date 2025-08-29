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
      <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg">
        <path d="M224,115.55V208a16,16,0,0,1-16,16H168a16,16,0,0,1-16-16V168a8,8,0,0,0-8-8H112a8,8,0,0,0-8,8v40a16,16,0,0,1-16,16H48a16,16,0,0,1-16-16V115.55a16,16,0,0,1,5.17-11.78l80-75.48.11-.11a16,16,0,0,1,21.53,0,1.14,1.14,0,0,0,.11.11l80,75.48A16,16,0,0,1,224,115.55Z"></path>
      </svg>
      <span class="text-sm">Dashboard</span>
    </a>

    <a href="{{ route('transactions.index') }}"
      class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
              {{ request()->routeIs('transactions.index') 
                 ? 'bg-purple-700 text-white font-semibold shadow-lg'
                 : 'hover:bg-gray-200 hover:text-gray-900' }}">
      {{-- Ikon Transaksi (Arrows Right Left) --}}
      <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg">
        <path d="M80,64a8,8,0,0,1,8-8H216a8,8,0,0,1,0,16H88A8,8,0,0,1,80,64Zm136,56H88a8,8,0,0,0,0,16H216a8,8,0,0,0,0-16Zm0,64H88a8,8,0,0,0,0,16H216a8,8,0,0,0,0-16ZM44,52A12,12,0,1,0,56,64,12,12,0,0,0,44,52Zm0,64a12,12,0,1,0,12,12A12,12,0,0,0,44,116Zm0,64a12,12,0,1,0,12,12A12,12,0,0,0,44,180Z"></path>
      </svg>
      <span class="text-sm">Transaksi</span>
    </a>

    <a href="{{ route('categories.index') }}"
      class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
              {{ request()->routeIs('categories.index') 
                 ? 'bg-purple-700 text-white font-semibold shadow-lg'
                 : 'hover:bg-gray-200 hover:text-gray-900' }}">
      {{-- Ikon Kategori (Tag) --}}
      <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg">
        <path d="M243.31,136,144,36.69A15.86,15.86,0,0,0,132.69,32H40a8,8,0,0,0-8,8v92.69A15.86,15.86,0,0,0,36.69,144L136,243.31a16,16,0,0,0,22.63,0l84.68-84.68a16,16,0,0,0,0-22.63Zm-96,96L48,132.69V48h84.69L232,147.31ZM96,84A12,12,0,1,1,84,72,12,12,0,0,1,96,84Z"></path>
      </svg>
      <span class="text-sm">Kategori</span>
    </a>

    <a href="{{ route('reports') }}"
      class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
              {{ request()->routeIs('reports') 
                 ? 'bg-purple-700 text-white font-semibold shadow-lg'
                 : 'hover:bg-gray-200 hover:text-gray-900' }}">
      {{-- Ikon Laporan (Chart Pie) --}}
      <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg">
        <path d="M216,40H136V24a8,8,0,0,0-16,0V40H40A16,16,0,0,0,24,56V176a16,16,0,0,0,16,16H79.36L57.75,219a8,8,0,0,0,12.5,10l29.59-37h56.32l29.59,37a8,8,0,1,0,12.5-10l-21.61-27H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,136H40V56H216V176ZM104,120v24a8,8,0,0,1-16,0V120a8,8,0,0,1,16,0Zm32-16v40a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Zm32-16v56a8,8,0,0,1-16,0V88a8,8,0,0,1,16,0Z"></path>
      </svg>
      <span class="text-sm">Laporan</span>
    </a>

    <a href="{{ route('debts.index') }}"
      class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors
              {{ request()->routeIs('debts.index') 
                 ? 'bg-purple-700 text-white font-semibold shadow-lg'
                 : 'hover:bg-gray-200 hover:text-gray-900' }}">
      {{-- Ikon Hutang (Receipt Percent) --}}
      <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg">
        <path d="M224,48H32A16,16,0,0,0,16,64V192a16,16,0,0,0,16,16H224a16,16,0,0,0,16-16V64A16,16,0,0,0,224,48Zm0,16V88H32V64Zm0,128H32V104H224v88Zm-16-24a8,8,0,0,1-8,8H168a8,8,0,0,1,0-16h32A8,8,0,0,1,208,168Zm-64,0a8,8,0,0,1-8,8H120a8,8,0,0,1,0-16h16A8,8,0,0,1,144,168Z"></path>
      </svg>
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
      <svg fill="currentColor" height="24px" viewBox="0 0 256 256" width="24px" xmlns="http://www.w3.org/2000/svg">
        <path d="M128,80a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160Zm88-29.84q.06-2.16,0-4.32l14.92-18.64a8,8,0,0,0,1.48-7.06,107.21,107.21,0,0,0-10.88-26.25,8,8,0,0,0-6-3.93l-23.72-2.64q-1.48-1.56-3-3L186,40.54a8,8,0,0,0-3.94-6,107.71,107.71,0,0,0-26.25-10.87,8,8,0,0,0-7.06,1.49L130.16,40Q128,40,125.84,40L107.2,25.11a8,8,0,0,0-7.06-1.48A107.6,107.6,0,0,0,73.89,34.51a8,8,0,0,0-3.93,6L67.32,64.27q-1.56,1.49-3,3L40.54,70a8,8,0,0,0-6,3.94,107.71,107.71,0,0,0-10.87,26.25,8,8,0,0,0,1.49,7.06L40,125.84Q40,128,40,130.16L25.11,148.8a8,8,0,0,0-1.48,7.06,107.21,107.21,0,0,0,10.88,26.25,8,8,0,0,0,6,3.93l23.72,2.64q1.49,1.56,3,3L70,215.46a8,8,0,0,0,3.94,6,107.71,107.71,0,0,0,26.25,10.87,8,8,0,0,0,7.06-1.49L125.84,216q2.16.06,4.32,0l18.64,14.92a8,8,0,0,0,7.06,1.48,107.21,107.21,0,0,0,26.25-10.88,8,8,0,0,0,3.93-6l2.64-23.72q1.56-1.48,3-3L215.46,186a8,8,0,0,0,6-3.94,107.71,107.71,0,0,0,10.87-26.25,8,8,0,0,0-1.49-7.06Zm-16.1-6.5a73.93,73.93,0,0,1,0,8.68,8,8,0,0,0,1.74,5.48l14.19,17.73a91.57,91.57,0,0,1-6.23,15L187,173.11a8,8,0,0,0-5.1,2.64,74.11,74.11,0,0,1-6.14,6.14,8,8,0,0,0-2.64,5.1l-2.51,22.58a91.32,91.32,0,0,1-15,6.23l-17.74-14.19a8,8,0,0,0-5-1.75h-.48a73.93,73.93,0,0,1-8.68,0,8,8,0,0,0-5.48,1.74L100.45,215.8a91.57,91.57,0,0,1-15-6.23L82.89,187a8,8,0,0,0-2.64-5.1,74.11,74.11,0,0,1-6.14-6.14,8,8,0,0,0-5.1-2.64L46.43,170.6a91.32,91.32,0,0,1-6.23-15l14.19-17.74a8,8,0,0,0,1.74-5.48,73.93,73.93,0,0,1,0-8.68,8,8,0,0,0-1.74-5.48L40.2,100.45a91.57,91.57,0,0,1,6.23-15L69,82.89a8,8,0,0,0,5.1-2.64,74.11,74.11,0,0,1,6.14-6.14A8,8,0,0,0,82.89,69L85.4,46.43a91.32,91.32,0,0,1,15-6.23l17.74,14.19a8,8,0,0,0,5.48,1.74,73.93,73.93,0,0,1,8.68,0,8,8,0,0,0,5.48-1.74L155.55,40.2a91.57,91.57,0,0,1,15,6.23L173.11,69a8,8,0,0,0,2.64,5.1,74.11,74.11,0,0,1,6.14,6.14,8,8,0,0,0,5.1,2.64l22.58,2.51a91.32,91.32,0,0,1,6.23,15l-14.19,17.74A8,8,0,0,0,199.87,123.66Z"></path>
      </svg>
      <span class="text-sm">Pengaturan</span>
    </a>
  </div>
</aside>