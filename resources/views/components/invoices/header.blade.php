@props(['invoice', 'logoData'])

@if($logoData)
<div class="invoice-watermark" style="background-image: url('data:image/webp;base64,{{ $logoData }}');"></div>
@endif

<div class="invoice-header">
    <div class="header-pattern"></div>
    
    <div class="header-content">
        <div class="header-top-row">
            <div class="company-logo">
                @if($logoData)
                    <img src="data:image/webp;base64,{{ $logoData }}" alt="Logo" class="company-logo-img">
                @else
                    <div class="logo-icon">V</div>
                @endif
            </div>
            
            <div class="invoice-date-box">
                <div class="invoice-date">Tanggal Invoice: {{ ($invoice->issue_date ?? $invoice->created_at)->format('d F Y') }}</div>
            </div>
        </div>
        
        <div class="invoice-to-box">
            <div class="invoice-to-content">
                <div class="invoice-to-label">Tagihan Kepada</div>
                <div class="client-info">
                    <p><strong>{{ $invoice->client_name ?? 'Tidak Tersedia' }}</strong></p>
                    @if($invoice->client_address)
                    <p>{{ $invoice->client_address }}</p>
                    @endif
                    @if($invoice->client_whatsapp)
                    <p>Telepon: {{ $invoice->client_whatsapp }}</p>
                    @endif
                    @if($invoice->client_email)
                    <p>Email: {{ $invoice->client_email }}</p>
                    @endif
                </div>
            </div>
            
            <div class="invoice-title-section">
                <h2 class="invoice-title">INVOICE</h2>
                <div class="invoice-number-barcode">
                    <div class="invoice-number">{{ $invoice->number }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

