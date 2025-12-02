<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/webp">
    <title>Invoice {{ $invoice->number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        {!! file_get_contents(resource_path('css/invoice-print.css')) !!}
        
        @media print {
            body {
                background: white !important;
                background-image: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-900" style="background-image: url('{{ asset('background-vodeco.jpg') }}'); background-size: cover; background-position: center; background-attachment: fixed;">
    <!-- Print Button -->
    <div class="no-print" style="max-width: 900px; margin: 0 auto 20px; text-align: right; padding: 0 10px;">
        <button 
            onclick="window.print()" 
            style="background: linear-gradient(135deg, #8615D9 0%, #3526B3 100%);"
            class="text-white px-6 py-2 rounded-lg font-medium transition-colors hover:opacity-90"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" style="display:inline;vertical-align:middle;margin-right:6px" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <rect x="5" y="13" width="14" height="7" rx="2" stroke-width="2"></rect>
                <path stroke-width="2" d="M7 13V7a2 2 0 012-2h6a2 2 0 012 2v6" />
                <rect x="9" y="17" width="6" height="2" rx="1" stroke-width="2" />
            </svg>
            Cetak Invoice
        </button>
    </div>

    <div class="invoice-wrapper">
        <x-invoices.header :invoice="$invoice" :logoData="$logoData" />
        
        <x-invoices.items :invoice="$invoice" />
        
        <x-invoices.summary 
            :subtotal="$subtotal"
            :tax="$tax"
            :discount="$discount"
            :downPaymentDue="$downPaymentDue"
            :totalDue="$totalDue"
        />

        <x-invoices.payment 
            :bcaInfo="$bcaInfo"
            :mandiriInfo="$mandiriInfo"
            :bank1LogoData="$bank1LogoData"
            :bank1LogoMime="$bank1LogoMime"
            :bank2LogoData="$bank2LogoData"
            :bank2LogoMime="$bank2LogoMime"
            :signatureData="$signatureData"
            :settings="$settings"
        />
        
        <x-invoices.footer 
            :companyAddress="$companyAddress"
            :companyPhone="$companyPhone"
            :companyEmail="$companyEmail"
            :companyWebsite="$companyWebsite"
        />
    </div>
    
    <x-invoices.scripts />
</body>
</html>
