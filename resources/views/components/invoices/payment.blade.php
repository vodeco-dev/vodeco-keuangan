@props(['bcaInfo', 'mandiriInfo', 'bank1LogoData', 'bank1LogoMime', 'bank2LogoData', 'bank2LogoMime', 'signatureData', 'settings'])

<div class="signature-payment-wrapper">
    <div style="flex: 1;">
        <div class="terms-title" style="font-size: 11px; margin-bottom: 15px;">Metode Pembayaran:</div>
        <div class="bank-container">
            <div class="bank-info-item">
                @if($bank1LogoData)
                    <img src="data:image/{{ $bank1LogoMime }};base64,{{ $bank1LogoData }}" alt="{{ $bcaInfo['name'] }} Logo" class="bank-logo">
                @endif
                <div class="bank-info-text">
                    <p style="margin: 0; font-size: 10px; line-height: 1.6;"><strong>{{ $bcaInfo['name'] }}:</strong></p>
                    <div class="account-number-wrapper" style="margin: 2px 0;">
                        <p class="account-number-text" id="bca-account">{{ $bcaInfo['account_number'] }}</p>
                        <button class="copy-btn" onclick="copyAccountNumber('{{ $bcaInfo['account_number'] }}', 'bca-copy-btn')" id="bca-copy-btn" title="Salin nomor rekening">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                        </button>
                    </div>
                    <p style="margin: 2px 0 0 0; font-size: 10px; line-height: 1.6;">a.n. {{ $bcaInfo['account_name'] }}</p>
                </div>
            </div>
            <div class="bank-info-item">
                @if($bank2LogoData)
                    <img src="data:image/{{ $bank2LogoMime }};base64,{{ $bank2LogoData }}" alt="{{ $mandiriInfo['name'] }} Logo" class="bank-logo">
                @endif
                <div class="bank-info-text">
                    <p style="margin: 0; font-size: 10px; line-height: 1.6;"><strong>{{ $mandiriInfo['name'] }}:</strong></p>
                    <div class="account-number-wrapper" style="margin: 2px 0;">
                        <p class="account-number-text" id="mandiri-account">{{ $mandiriInfo['account_number'] }}</p>
                        <button class="copy-btn" onclick="copyAccountNumber('{{ $mandiriInfo['account_number'] }}', 'mandiri-copy-btn')" id="mandiri-copy-btn" title="Salin nomor rekening">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                        </button>
                    </div>
                    <p style="margin: 2px 0 0 0; font-size: 10px; line-height: 1.6;">a.n. {{ $mandiriInfo['account_name'] }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="signature-section">
        @if($signatureData)
            <img src="data:image/png;base64,{{ $signatureData }}" alt="Tanda Tangan" class="signature-image">
        @endif
        <div class="signature-name">{{ $settings['signature_name'] ?? 'Gibranio Zelmy' }}</div>
        <div class="signature-title">{{ $settings['signature_title'] ?? 'Vodeco Media group' }}</div>
    </div>
</div>

