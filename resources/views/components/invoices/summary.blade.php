@props(['subtotal', 'tax', 'discount', 'downPaymentDue', 'totalDue'])

<div class="summary-section">
    <div class="financial-summary">
        @if($downPaymentDue > 0)
        <div class="summary-row">
            <span class="summary-label">DOWN PAYMENT</span>
            <span class="summary-value">Rp {{ number_format($downPaymentDue, 0, ',', '.') }}</span>
        </div>
        @endif
        <div class="summary-row">
            <span class="summary-label">Sisa Pembayaran</span>
            <span class="summary-value">Rp {{ number_format(max($subtotal - $downPaymentDue, 0), 0, ',', '.') }}</span>
        </div>
        @if($tax > 0)
        <div class="summary-row">
            <span class="summary-label">Pajak PPN 15%</span>
            <span class="summary-value">Rp {{ number_format($tax, 0, ',', '.') }}</span>
        </div>
        @endif
        @if($discount > 0)
        <div class="summary-row">
            <span class="summary-label">DISKON 5%</span>
            <span class="summary-value">Rp {{ number_format($discount, 0, ',', '.') }}</span>
        </div>
        @endif
        <div class="total-due-box">
            <div class="total-due-label">SUB TOTAL</div>
            <div class="total-due-value">Rp {{ number_format($totalDue, 0, ',', '.') }}</div>
        </div>
    </div>
    
    <div class="terms-section">
        <div class="terms-title">Syarat & Ketentuan</div>
        <div class="terms-text">
            <p>Dengan melakukan pembayaran invoice ini, Anda menyatakan telah membaca, memahami, dan menyetujui seluruh Syarat & Ketentuan yang berlaku di Vodeco.</p>
            <p style="margin-top: 8px;">• Pelanggan wajib membayar tagihan tepat waktu sesuai tenggat jatuh tempo.</p>
            <p>• Isi konten layanan merupakan tanggung jawab pelanggan.</p>
            <p>• Revisi tidak termasuk penambahan fitur atau halaman di luar paket.</p>
            <p style="margin-top: 8px; font-style: italic;">Syarat & Ketentuan lengkap: <a href="https://vodeco.co.id/syarat-ketentuan" target="_blank" class="terms-link">vodeco.co.id/syarat-ketentuan</a></p>
        </div>
        
        <div class="thank-you">Terima kasih atas kepercayaan Anda</div>
    </div>
</div>

