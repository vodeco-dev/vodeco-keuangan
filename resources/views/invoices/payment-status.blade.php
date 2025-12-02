<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pembayaran - Invoice {{ $invoice->number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: 'Arial', sans-serif;
        }
        
        .status-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }
        
        .status-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .status-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .status-title {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .invoice-number {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        .status-card {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .status-label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .status-value {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .status-description {
            font-size: 14px;
            color: #6b7280;
            margin-top: 15px;
            line-height: 1.6;
        }
        
        .info-section {
            margin-top: 30px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-size: 14px;
            color: #6b7280;
        }
        
        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .back-button {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: opacity 0.3s;
        }
        
        .back-button:hover {
            opacity: 0.9;
        }
        
        .status-lunas {
            color: #10b981;
        }
        
        .status-belum-lunas {
            color: #f59e0b;
        }
        
        .status-belum-bayar {
            color: #ef4444;
        }
        
        .status-menunggu {
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <div class="status-container">
        <div class="status-header">
            <div class="status-icon">
                @if($statusInfo['color'] === 'green')
                    ✅
                @elseif($statusInfo['color'] === 'yellow')
                    ⏳
                @elseif($statusInfo['color'] === 'orange')
                    💰
                @elseif($statusInfo['color'] === 'red')
                    ⚠️
                @else
                    📝
                @endif
            </div>
            <h1 class="status-title">{{ $statusInfo['label'] }}</h1>
            <div class="invoice-number">Invoice #{{ $invoice->number }}</div>
        </div>
        
        <div class="status-card">
            <div class="status-label">Status Pembayaran</div>
            <div class="status-value status-{{ str_replace(' ', '-', strtolower($statusInfo['label'])) }}">
                {{ $statusInfo['label'] }}
            </div>
            <div class="status-description">
                {{ $statusInfo['description'] }}
            </div>
        </div>
        
        <div class="info-section">
            <div class="info-item">
                <span class="info-label">Total Invoice</span>
                <span class="info-value">Rp {{ number_format($invoice->total, 0, ',', '.') }}</span>
            </div>
            @if($invoice->down_payment > 0)
            <div class="info-item">
                <span class="info-label">Down Payment</span>
                <span class="info-value">Rp {{ number_format($invoice->down_payment, 0, ',', '.') }}</span>
            </div>
            @endif
            @php
                $remaining = max((float) $invoice->total - (float) $invoice->down_payment, 0);
            @endphp
            @if($remaining > 0)
            <div class="info-item">
                <span class="info-label">Sisa Tagihan</span>
                <span class="info-value">Rp {{ number_format($remaining, 0, ',', '.') }}</span>
            </div>
            @endif
            @if($invoice->issue_date)
            <div class="info-item">
                <span class="info-label">Tanggal Invoice</span>
                <span class="info-value">{{ $invoice->issue_date->format('d F Y') }}</span>
            </div>
            @endif
            @if($invoice->due_date)
            <div class="info-item">
                <span class="info-label">Jatuh Tempo</span>
                <span class="info-value">{{ $invoice->due_date->format('d F Y') }}</span>
            </div>
            @endif
            @if($statusInfo['payment_date'])
            <div class="info-item">
                <span class="info-label">Tanggal Pembayaran</span>
                <span class="info-value">{{ $statusInfo['payment_date']->format('d F Y') }}</span>
            </div>
            @endif
            @if($statusInfo['has_payment_proof'])
            <div class="info-item">
                <span class="info-label">Bukti Pembayaran</span>
                <span class="info-value" style="color: #10b981;">✓ Tersedia</span>
            </div>
            @endif
        </div>
        
        <div style="text-align: center;">
            <a href="{{ route('invoices.public.detail', $invoice->public_token) }}" class="back-button">
                ← Kembali ke Invoice
            </a>
        </div>
    </div>
</body>
</html>

