<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Invoice</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        /* Liquid Glass Effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        .glass-button {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px) saturate(180%);
            -webkit-backdrop-filter: blur(10px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .glass-button:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 16px 0 rgba(31, 38, 135, 0.2);
        }
        body {
            position: relative;
            min-height: 100vh;
            overflow-x: hidden;
            color: #ffffff;
        }
        .input-glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px) saturate(180%);
            -webkit-backdrop-filter: blur(10px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
        }
        .input-glass:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
        }
        .input-glass::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        .text-primary {
            color: #ffffff;
        }
        .text-secondary {
            color: rgba(255, 255, 255, 0.8);
        }
        .text-muted {
            color: rgba(255, 255, 255, 0.6);
        }
        .border-divider {
            border-color: rgba(255, 255, 255, 0.18);
        }
        /* Fix untuk layout yang slip */
        .glass-card {
            overflow: hidden;
        }
        /* Memastikan nomor invoice tidak terpecah dengan tidak tepat */
        .break-all {
            word-break: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>
<body class="bg-gray-900 text-primary" style="background-image: url('{{ asset('background-vodeco.jpg') }}'); background-size: cover; background-position: center; background-attachment: fixed;">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12 relative z-10">
        <!-- Header -->
        <div class="text-center mb-10 fade-in">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full glass-card mb-6">
                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold mb-3 text-primary">Cek Status Invoice</h1>
            <p class="text-secondary text-lg">Masukkan nomor invoice untuk melihat detail</p>
        </div>

        <!-- Search Form Card -->
        <div class="glass-card rounded-3xl p-8 mb-8 fade-in shadow-2xl">
            <form id="searchForm" class="space-y-6">
                <div>
                    <label for="search" class="block text-sm font-medium text-secondary mb-3">
                        Nomor Invoice
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="search" 
                            name="search" 
                            value="{{ request('search') }}"
                            placeholder="Contoh: INV-2024-001" 
                            class="input-glass w-full px-6 py-4 rounded-2xl focus:ring-2 focus:ring-white/20 transition-all duration-300 text-lg"
                            autofocus
                        >
                        <div class="absolute inset-y-0 right-0 flex items-center pr-5">
                            <svg class="w-6 h-6 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="flex gap-4">
                    <button 
                        type="submit" 
                        id="searchButton"
                        class="flex-1 px-6 py-4 glass-button text-primary rounded-2xl focus:outline-none focus:ring-2 focus:ring-white/30 transition-all duration-300 font-semibold text-lg"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <svg id="searchIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <span id="searchButtonText">Cari Invoice</span>
                            <svg id="loadingSpinner" class="hidden w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                    <button 
                        type="button" 
                        id="resetButton"
                        class="hidden px-6 py-4 glass-button text-primary rounded-2xl focus:outline-none focus:ring-2 focus:ring-white/30 transition-all duration-300 font-semibold text-lg"
                    >
                        Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Section -->
        <div id="resultsSection" class="fade-in space-y-6" style="display: none;">
            <div id="resultsContainer"></div>
            <div id="paginationContainer" class="mt-8 flex justify-center"></div>
        </div>
        
        <!-- Original Results (for initial page load with search parameter) -->
        @if(request('search'))
        <div id="initialResults" class="fade-in space-y-6">
            @forelse($invoices as $invoice)
            @php
                // Calculate subtotal from items
                $subtotal = $invoice->items->sum(fn($item) => $item->price * $item->quantity);
                // Map status
                $statusClass = 'border-gray-500';
                $statusText = strtoupper($invoice->status);
                $statusColor = 'text-gray-400';
                if ($invoice->status == 'lunas') {
                    $statusClass = 'border-green-500';
                    $statusColor = 'text-green-400';
                } elseif ($invoice->status == 'belum lunas') {
                    $statusClass = 'border-yellow-500';
                    $statusColor = 'text-yellow-400';
                } elseif ($invoice->status == 'belum bayar') {
                    $statusClass = 'border-red-500';
                    $statusColor = 'text-red-400';
                }
            @endphp
            <div class="glass-card rounded-3xl p-8 shadow-2xl border-l-4 {{ $statusClass }}">
                <!-- Header Section -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 pb-6 border-b border-divider">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="w-14 h-14 rounded-2xl glass-button flex items-center justify-center flex-shrink-0">
                            <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-2xl sm:text-3xl font-bold mb-2 text-primary break-all">{{ $invoice->number }}</h2>
                            <p class="text-secondary text-sm">
                                @if($invoice->issue_date)
                                    {{ $invoice->issue_date->format('d M Y') }}
                                @else
                                    {{ $invoice->created_at->format('d M Y') }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center justify-start sm:justify-end flex-shrink-0">
                        <span class="glass-button px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap {{ $statusColor }}">
                            {{ $statusText }}
                        </span>
                    </div>
                </div>

                <!-- Client Info -->
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-secondary uppercase mb-4">Client Information</h3>
                    <div class="glass-button rounded-2xl p-5">
                        <p class="text-xl font-bold mb-3 text-primary">{{ $invoice->client_name ?? 'N/A' }}</p>
                        <div class="space-y-2 text-secondary">
                            @if($invoice->client_whatsapp)
                            <p class="flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                {{ $invoice->client_whatsapp }}
                            </p>
                            @endif
                            @if($invoice->client_address)
                            <p class="flex items-start gap-2 text-sm">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>{{ $invoice->client_address }}</span>
                            </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-secondary uppercase mb-4">Invoice Details</h3>
                    <div class="glass-button rounded-2xl p-5 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-secondary">Due Date:</span>
                            <span class="font-semibold text-primary">
                                @if($invoice->due_date)
                                    {{ $invoice->due_date->format('d M Y') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-secondary">Subtotal:</span>
                            <span class="font-semibold text-primary">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="pt-4 border-t border-divider flex justify-between items-center">
                            <span class="text-lg font-bold text-primary">Total:</span>
                            <span class="text-3xl font-bold text-primary">Rp {{ number_format($invoice->total, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a 
                        href="{{ route('invoices.public.detail', $invoice->public_token) }}" 
                        class="flex-1 px-6 py-4 glass-button text-primary rounded-2xl focus:outline-none focus:ring-2 focus:ring-white/30 transition-all duration-300 font-semibold text-center"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Lihat Detail
                        </span>
                    </a>
                    <a 
                        href="{{ route('invoices.public.print', $invoice->public_token) }}" 
                        target="_blank"
                        class="flex-1 px-6 py-4 glass-button text-primary rounded-2xl focus:outline-none focus:ring-2 focus:ring-white/30 transition-all duration-300 font-semibold text-center"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                            Cetak Invoice
                        </span>
                    </a>
                </div>
            </div>
            @empty
            <!-- Empty State -->
            <div class="glass-card rounded-3xl p-12 text-center shadow-2xl">
                <div class="max-w-md mx-auto">
                    <div class="w-20 h-20 mx-auto mb-6 rounded-full glass-button flex items-center justify-center">
                        <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-3 text-primary">Invoice Tidak Ditemukan</h3>
                    <p class="text-secondary mb-6">
                        Tidak ada invoice dengan nomor <span class="font-semibold text-primary">"{{ request('search') }}"</span>. 
                        Pastikan nomor invoice yang Anda masukkan sudah benar.
                    </p>
                    <a 
                        href="{{ route('invoices.public.search') }}" 
                        class="inline-flex items-center px-6 py-3 glass-button text-primary rounded-2xl focus:outline-none focus:ring-2 focus:ring-white/30 transition-all duration-300 font-semibold"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Cari Lagi
                    </a>
                </div>
            </div>
            @endforelse

            <!-- Pagination -->
            @if($invoices->hasPages())
            <div class="mt-8 flex justify-center">
                <div class="glass-card rounded-2xl p-4">
                    {{ $invoices->links() }}
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.getElementById('searchForm');
            const searchInput = document.getElementById('search');
            const searchButton = document.getElementById('searchButton');
            const resetButton = document.getElementById('resetButton');
            const resultsSection = document.getElementById('resultsSection');
            const resultsContainer = document.getElementById('resultsContainer');
            const paginationContainer = document.getElementById('paginationContainer');
            const searchIcon = document.getElementById('searchIcon');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const searchButtonText = document.getElementById('searchButtonText');
            const initialResults = document.getElementById('initialResults');

            // Hide initial results if exists (will be replaced by AJAX results)
            if (initialResults) {
                initialResults.style.display = 'none';
            }

            // Show reset button if search input has value
            if (searchInput.value.trim()) {
                resetButton.classList.remove('hidden');
                // If page loaded with search parameter, hide initial results and show AJAX results
                if (initialResults) {
                    initialResults.style.display = 'none';
                    performSearch();
                }
            }

            // Handle form submission
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                performSearch();
            });

            // Handle reset button
            resetButton.addEventListener('click', function() {
                searchInput.value = '';
                resetButton.classList.add('hidden');
                resultsSection.style.display = 'none';
                if (initialResults) {
                    initialResults.style.display = 'none';
                }
                searchInput.focus();
            });

            // Show reset button when typing
            searchInput.addEventListener('input', function() {
                if (this.value.trim()) {
                    resetButton.classList.remove('hidden');
                } else {
                    resetButton.classList.add('hidden');
                }
            });

            function performSearch(page = 1) {
                const searchTerm = searchInput.value.trim();
                
                if (!searchTerm) {
                    return;
                }

                performSearchWithPage(page, searchTerm);
            }

            function performSearchWithPage(page = 1, searchTerm = null) {
                const term = searchTerm || searchInput.value.trim();
                
                if (!term) {
                    return;
                }

                // Show loading state
                searchButton.disabled = true;
                searchIcon.classList.add('hidden');
                loadingSpinner.classList.remove('hidden');
                searchButtonText.textContent = 'Mencari...';

                // Hide initial results
                if (initialResults) {
                    initialResults.style.display = 'none';
                }

                // Build URL with search and page
                const url = new URL(window.location.href);
                url.searchParams.set('search', term);
                if (page > 1) {
                    url.searchParams.set('page', page);
                } else {
                    url.searchParams.delete('page');
                }
                window.history.pushState({}, '', url);

                // Build fetch URL
                let fetchUrl = `{{ route('invoices.public.search') }}?search=${encodeURIComponent(term)}`;
                if (page > 1) {
                    fetchUrl += `&page=${page}`;
                }

                // Perform AJAX request
                fetch(fetchUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Hide loading state
                    searchButton.disabled = false;
                    searchIcon.classList.remove('hidden');
                    loadingSpinner.classList.add('hidden');
                    searchButtonText.textContent = 'Cari Invoice';

                    if (data.success) {
                        displayResults(data);
                    } else {
                        displayError('Terjadi kesalahan saat mencari invoice.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    searchButton.disabled = false;
                    searchIcon.classList.remove('hidden');
                    loadingSpinner.classList.add('hidden');
                    searchButtonText.textContent = 'Cari Invoice';
                    displayError('Terjadi kesalahan saat mencari invoice. Silakan coba lagi.');
                });
            }

            function displayResults(data) {
                resultsContainer.innerHTML = '';

                if (data.invoices && data.invoices.length > 0) {
                    data.invoices.forEach(invoice => {
                        const invoiceCard = createInvoiceCard(invoice);
                        resultsContainer.appendChild(invoiceCard);
                    });

                    // Display pagination
                    if (data.has_pages && data.pagination_html) {
                        paginationContainer.innerHTML = '<div class="glass-card rounded-2xl p-4">' + data.pagination_html + '</div>';
                        
                        // Handle pagination links
                        setTimeout(() => {
                            paginationContainer.querySelectorAll('a').forEach(link => {
                                link.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    const url = new URL(this.href);
                                    const pageSearch = url.searchParams.get('search');
                                    const page = url.searchParams.get('page') || '1';
                                    
                                    if (pageSearch) {
                                        searchInput.value = pageSearch;
                                        performSearchWithPage(page);
                                    }
                                });
                            });
                        }, 100);
                    } else {
                        paginationContainer.innerHTML = '';
                    }

                    resultsSection.style.display = 'block';
                } else {
                    displayEmptyState(data.search_term);
                }
            }

            function createInvoiceCard(invoice) {
                const card = document.createElement('div');
                card.className = `glass-card rounded-3xl p-8 shadow-2xl border-l-4 ${invoice.status_class}`;
                
                card.innerHTML = `
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 pb-6 border-b border-divider">
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            <div class="w-14 h-14 rounded-2xl glass-button flex items-center justify-center flex-shrink-0">
                                <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h2 class="text-2xl sm:text-3xl font-bold mb-2 text-primary break-all">${invoice.number}</h2>
                                <p class="text-secondary text-sm">${invoice.issue_date}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-start sm:justify-end flex-shrink-0">
                            <span class="glass-button px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap ${invoice.status_color}">
                                ${invoice.status_text}
                            </span>
                        </div>
                    </div>
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-secondary uppercase mb-4">Client Information</h3>
                        <div class="glass-button rounded-2xl p-5">
                            <p class="text-xl font-bold mb-3 text-primary">${invoice.client_name}</p>
                            <div class="space-y-2 text-secondary">
                                ${invoice.client_whatsapp ? `
                                <p class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    ${invoice.client_whatsapp}
                                </p>
                                ` : ''}
                                ${invoice.client_address ? `
                                <p class="flex items-start gap-2 text-sm">
                                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span>${invoice.client_address}</span>
                                </p>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-secondary uppercase mb-4">Invoice Details</h3>
                        <div class="glass-button rounded-2xl p-5 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-secondary">Due Date:</span>
                                <span class="font-semibold text-primary">
                                    ${invoice.due_date || '<span class="text-muted">-</span>'}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-secondary">Subtotal:</span>
                                <span class="font-semibold text-primary">Rp ${formatNumber(invoice.subtotal)}</span>
                            </div>
                            <div class="pt-4 border-t border-divider flex justify-between items-center">
                                <span class="text-lg font-bold text-primary">Total:</span>
                                <span class="text-3xl font-bold text-primary">Rp ${formatNumber(invoice.total)}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a 
                            href="${invoice.detail_url}" 
                            class="flex-1 px-6 py-4 glass-button text-primary rounded-2xl focus:outline-none focus:ring-2 focus:ring-white/30 transition-all duration-300 font-semibold text-center"
                        >
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Lihat Detail
                            </span>
                        </a>
                        <a 
                            href="${invoice.print_url}" 
                            target="_blank"
                            class="flex-1 px-6 py-4 glass-button text-primary rounded-2xl focus:outline-none focus:ring-2 focus:ring-white/30 transition-all duration-300 font-semibold text-center"
                        >
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                </svg>
                                Cetak Invoice
                            </span>
                        </a>
                    </div>
                `;
                
                return card;
            }

            function displayEmptyState(searchTerm) {
                resultsContainer.innerHTML = `
                    <div class="glass-card rounded-3xl p-12 text-center shadow-2xl">
                        <div class="max-w-md mx-auto">
                            <div class="w-20 h-20 mx-auto mb-6 rounded-full glass-button flex items-center justify-center">
                                <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold mb-3 text-primary">Invoice Tidak Ditemukan</h3>
                            <p class="text-secondary mb-6">
                                Tidak ada invoice dengan nomor <span class="font-semibold text-primary">"${searchTerm}"</span>. 
                                Pastikan nomor invoice yang Anda masukkan sudah benar.
                            </p>
                            <button 
                                onclick="document.getElementById('search').focus()"
                                class="inline-flex items-center px-6 py-3 glass-button text-primary rounded-2xl focus:outline-none focus:ring-2 focus:ring-white/30 transition-all duration-300 font-semibold"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Cari Lagi
                            </button>
                        </div>
                    </div>
                `;
                paginationContainer.innerHTML = '';
                resultsSection.style.display = 'block';
            }

            function displayError(message) {
                resultsContainer.innerHTML = `
                    <div class="glass-card rounded-3xl p-12 text-center shadow-2xl">
                        <div class="max-w-md mx-auto">
                            <div class="w-20 h-20 mx-auto mb-6 rounded-full glass-button flex items-center justify-center">
                                <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold mb-3 text-primary">Terjadi Kesalahan</h3>
                            <p class="text-secondary mb-6">${message}</p>
                        </div>
                    </div>
                `;
                paginationContainer.innerHTML = '';
                resultsSection.style.display = 'block';
            }

            function formatNumber(number) {
                return new Intl.NumberFormat('id-ID').format(number);
            }
        });
    </script>
</body>
</html>

