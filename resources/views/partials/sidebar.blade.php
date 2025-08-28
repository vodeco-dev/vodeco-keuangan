<aside class="flex flex-col w-64 bg-[var(--background-primary)] p-6 shrink-0">
  <div class="flex items-center gap-2 mb-8">
    <div class="w-8 h-8 rounded-full bg-[var(--primary-color)] flex items-center justify-center text-white font-bold text-lg">
      F
    </div>
    <h1 class="text-[var(--text-primary)] text-xl font-bold">FinanceFly</h1>
  </div>
  <nav class="flex flex-col gap-2">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-md bg-[var(--primary-color)] text-white">
      <span class="text-sm font-semibold">Dashboard</span>
    </a>
    <a href="{{ route('transactions.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-md text-[var(--text-secondary)] hover:bg-[var(--background-secondary)] hover:text-[var(--text-primary)] transition-colors">
      <span class="text-sm font-medium">Transaksi</span>
    </a>
    <a href="{{ route('categories.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-md text-[var(--text-secondary)] hover:bg-[var(--background-secondary)] hover:text-[var(--text-primary)] transition-colors">
      <span class="text-sm font-medium">Kategori</span>
    </a>
    <a href="{{ route('reports') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-md text-[var(--text-secondary)] hover:bg-[var(--background-secondary)] hover:text-[var(--text-primary)] transition-colors">
      <span class="text-sm font-medium">Laporan</span>
    </a>
    <a href="{{ route('debts') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-md text-[var(--text-secondary)] hover:bg-[var(--background-secondary)] hover:text-[var(--text-primary)] transition-colors">
      <span class="text-sm font-medium">Hutang</span>
    </a>
  </nav>
  <a href="{{ route('settings') }}" class="flex items-center gap-3 px-4 py-2.5 mt-auto rounded-md text-[var(--text-secondary)] hover:bg-[var(--background-secondary)] hover:text-[var(--text-primary)] transition-colors">
    <span class="text-sm font-medium">Pengaturan</span>
  </a>
</aside>
