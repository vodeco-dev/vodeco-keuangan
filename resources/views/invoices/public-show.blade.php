<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Print Color Adjust - Memaksa browser mencetak background */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        .header-block {
            background: linear-gradient(135deg, #3526B3 0%, #2a1d8f 100%) !important;
            position: relative;
            overflow: hidden;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .header-block::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 100%;
            background: #8615D9 !important;
            transform: skewX(-20deg);
            transform-origin: top right;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .table-header {
            background: #8615D9 !important;
            color: white !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .table-header-dark {
            background: #3526B3 !important;
            color: white !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .item-row-no {
            background: #f1f5f9 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .total-box {
            background: #8615D9 !important;
            color: white !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .footer-block {
            background: linear-gradient(135deg, #3526B3 0%, #2a1d8f 100%) !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .text-accent {
            color: #8615D9 !important;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 0;
                background: white !important;
            }
            .invoice-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
            /* Memastikan semua background tercetak */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            /* Memaksa background untuk elemen spesifik */
            .header-block,
            .table-header,
            .table-header-dark,
            .item-row-no,
            .total-box,
            .footer-block {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            /* Memastikan text putih tetap putih saat print */
            .table-header,
            .table-header-dark,
            .total-box,
            .footer-block {
                color: white !important;
            }
        }
    </style>
</head>
<body class="bg-gray-900" style="background-image: url('{{ asset('background-vodeco.jpg') }}'); background-size: cover; background-position: center; background-attachment: fixed;">
    <!-- Print Button -->
    <div class="no-print max-w-4xl mx-auto p-6">
        <button 
            onclick="window.print()" 
            style="background-color: #8615D9;"
            class="hover:opacity-90 text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center gap-2"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Print Invoice
        </button>
    </div>

    @php
        // Calculate subtotal from items
        $subtotal = $invoice->items->sum(fn($item) => $item->price * $item->quantity);
        $tax = 0; // vodeco-keuangan doesn't have tax field
        $discount = 0; // vodeco-keuangan doesn't have discount field
    @endphp

    <!-- Invoice Container -->
    <div class="max-w-4xl mx-auto bg-white invoice-container shadow-lg" style="min-height: 297mm;">
        <!-- Header Section -->
        <div class="header-block text-white p-8 flex justify-between items-start relative">
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 rounded flex items-center justify-center text-2xl font-bold text-white" style="background-color: #8615D9;">
                        Z
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">{{ config('app.name', 'COMPANY') }}</h1>
                        <p class="text-sm text-gray-300">{{ config('app.company_tagline', 'COMPANY TAGLINE HERE') }}</p>
                    </div>
                </div>
            </div>
            <div class="relative z-10 text-right">
                <h2 class="text-5xl font-bold mb-2 text-white">INVOICE</h2>
                <div class="text-sm text-gray-300">
                    <p>Invoice Number: #{{ $invoice->number }}</p>
                    <p>Invoice Date: {{ ($invoice->issue_date ?? $invoice->created_at)->format('F d, Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Invoice To & From Section -->
        <div class="p-8 grid grid-cols-2 gap-8">
            <!-- Invoice To -->
            <div>
                <h3 class="font-semibold mb-3 text-sm uppercase" style="color: #8615D9;">Invoice To:</h3>
                <p class="font-bold text-gray-900 mb-1">{{ $invoice->client_name ?? 'N/A' }}</p>
                @if($invoice->client_address)
                <p class="text-gray-600 text-sm mb-1">{{ $invoice->client_address }}</p>
                @endif
                @if($invoice->client_whatsapp)
                <p class="text-gray-600 text-sm mb-1">Phone: {{ $invoice->client_whatsapp }}</p>
                @endif
            </div>

            <!-- Invoice From -->
            <div>
                <h3 class="font-semibold mb-3 text-sm uppercase" style="color: #8615D9;">Invoice From:</h3>
                <p class="font-bold text-gray-900 mb-1">{{ config('app.name', 'Your Company Name') }}</p>
                <p class="text-gray-600 text-sm mb-1">{{ config('app.company_address', 'Your Company Address') }}</p>
                <p class="text-gray-600 text-sm mb-1">Phone: {{ config('app.company_phone', '+62 123 456 789') }}</p>
                <p class="text-gray-600 text-sm">Email: {{ config('app.company_email', 'your@email.com') }}</p>
            </div>
        </div>

        <!-- Product Table -->
        <div class="px-8 pb-8">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="table-header text-left py-3 px-4 font-semibold text-sm">NO.</th>
                        <th class="table-header text-left py-3 px-4 font-semibold text-sm">PRODUCT DESCRIPTION</th>
                        <th class="table-header-dark text-right py-3 px-4 font-semibold text-sm">PRICE</th>
                        <th class="table-header-dark text-center py-3 px-4 font-semibold text-sm">QTY.</th>
                        <th class="table-header-dark text-right py-3 px-4 font-semibold text-sm">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $index => $item)
                    <tr class="border-b border-gray-200">
                        <td class="item-row-no py-4 px-4 text-sm font-medium text-gray-700">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="py-4 px-4 text-sm text-gray-900 bg-white">{{ $item->description }}</td>
                        <td class="py-4 px-4 text-sm text-gray-900 bg-white text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td class="py-4 px-4 text-sm text-gray-900 bg-white text-center">{{ $item->quantity }}</td>
                        <td class="py-4 px-4 text-sm text-gray-900 bg-white text-right font-medium">Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary & Payment Section -->
        <div class="px-8 pb-8 grid grid-cols-2 gap-8">
            <!-- Payment Method -->
            <div>
                <h3 class="font-semibold mb-3 text-sm uppercase" style="color: #8615D9;">Payment Method:</h3>
                <div class="text-sm text-gray-700 space-y-1">
                    <p>Account No: {{ config('app.company_account_no', '1234 5678 910') }}</p>
                    <p>Account Name: {{ config('app.name', 'Your Company Name') }}</p>
                    <p>Branch Name: {{ config('app.company_branch', 'Main Branch') }}</p>
                </div>
                <div class="mt-6">
                    <div class="border-t-2 border-gray-300 pt-4 mt-4">
                        <p class="text-xs text-gray-500 mb-1">Authorised sign</p>
                        <div class="h-16 border-b-2 border-gray-400"></div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div>
                <div class="space-y-2 text-sm mb-4">
                    <div class="flex justify-between text-gray-700">
                        <span>Subtotal:</span>
                        <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($discount > 0)
                    <div class="flex justify-between text-gray-700">
                        <span>Discount:</span>
                        <span>Rp {{ number_format($discount, 0, ',', '.') }}</span>
                    </div>
                    @else
                    <div class="flex justify-between text-gray-700">
                        <span>Discount:</span>
                        <span>00.00</span>
                    </div>
                    @endif
                    @if($tax > 0)
                    <div class="flex justify-between text-gray-700">
                        <span>Tax:</span>
                        <span>Rp {{ number_format($tax, 0, ',', '.') }}</span>
                    </div>
                    @endif
                </div>
                <div class="total-box p-4 rounded">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-lg">Total:</span>
                        <span class="font-bold text-lg">Rp {{ number_format($invoice->total, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-block text-white p-6">
            <div class="flex justify-between items-center">
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <span>{{ config('app.company_phone', '+62 123 456 789') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>{{ config('app.company_email', 'your@email.com') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>{{ config('app.company_address', 'Your location here') }}</span>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-lg font-semibold">Thank You For Your Business</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

