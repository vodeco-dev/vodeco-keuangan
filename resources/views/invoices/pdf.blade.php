@php
    use App\Models\Invoice as InvoiceModel;

    $manifestPath = public_path('build/manifest.json');
    $manifest = is_file($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : null;
    $cssFile = $manifest['resources/css/app.css']['file'] ?? null;
    $cssPath = $cssFile ? public_path('build/' . $cssFile) : null;
    $cssContent = $cssPath && is_file($cssPath) ? file_get_contents($cssPath) : '';
    $logoPath = public_path($settings['company_logo'] ?? 'vodeco.webp');
    $logoData = base64_encode(file_get_contents($logoPath));
    $signaturePath = public_path($settings['signature_image'] ?? 'image3.png');
    $signatureData = base64_encode(file_get_contents($signaturePath));
    $headerBgPath = public_path('image4.png');
    $headerBgData = base64_encode(file_get_contents($headerBgPath));

    $determineMime = function ($path) {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'jpg') {
            $extension = 'jpeg';
        }

        return $extension ?: 'png';
    };

    $bank1LogoPath = public_path($settings['bank_1_logo'] ?? 'logo-bank-bca.png');
    $bank1LogoData = is_file($bank1LogoPath) ? base64_encode(file_get_contents($bank1LogoPath)) : null;
    $bank1LogoMime = $bank1LogoData ? $determineMime($bank1LogoPath) : null;

    $bank2LogoPath = public_path($settings['bank_2_logo'] ?? 'logo bank mandiri.png');
    $bank2LogoData = is_file($bank2LogoPath) ? base64_encode(file_get_contents($bank2LogoPath)) : null;
    $bank2LogoMime = $bank2LogoData ? $determineMime($bank2LogoPath) : null;
    $invoice->loadMissing('referenceInvoice');

    $transactionLabel = match (true) {
        $invoice->type === InvoiceModel::TYPE_SETTLEMENT => 'Pelunasan',
        ! is_null($invoice->down_payment_due) => 'Down Payment',
        default => 'Bayar Lunas',
    };

    $paymentStatusLabel = $invoice->status ? ucwords($invoice->status) : 'Menunggu Pembayaran';

    $referenceInvoice = $invoice->referenceInvoice;
    $settlementPaidAmount = (float) $invoice->down_payment;
    $remainingBeforeSettlement = null;
    $remainingAfterSettlement = null;
    $settlementStatusLabel = $invoice->status === 'lunas' ? 'Bayar Lunas' : 'Bayar Sebagian';

    if ($invoice->type === InvoiceModel::TYPE_SETTLEMENT && $referenceInvoice) {
        $referenceTotal = (float) $referenceInvoice->total;
        $referencePaidBefore = max((float) $referenceInvoice->down_payment - $settlementPaidAmount, 0);
        $remainingBeforeSettlement = max($referenceTotal - $referencePaidBefore, 0);
        $remainingAfterSettlement = max($referenceTotal - (float) $referenceInvoice->down_payment, 0);
    }
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        {!! $cssContent !!}
        @page {
            size: A4;
            margin: 0mm;
        }
        * {
            font-family: 'Arial', sans-serif;
        }
        body {
            background: url("public/image2.png") no-repeat center;
            margin: 0;
        }
    </style>
</head>

<body class="text-sm">
    <div style="position: absolute; top: 0; right: 0;">
        <img src="data:image/png;base64,{{ $headerBgData }}" style="width: 200px; height: auto;">
    </div>
    <div class="mx-auto max-w-[210mm] bg-white px-10 py-8">
        <!-- Header -->
        <table style="width: 100%;margin-top: 40px;">
            <tr>
                <td style="width: 50%; vertical-align: middle;">
                    <img src="data:image/webp;base64,{{ $logoData }}" alt="Vodeco Logo" class="w-48">
                </td>
                <td style="width: 50%; text-align: right; vertical-align: middle;">
                    <div class="rounded-lg p-4 text-right text-xs space-y-1">
                        <h1 class="font-bold text-base">&copy; {{ $settings['company_name'] ?? 'CV. Vodeco Digital Mediatama' }}</h1>
                        <p>{{ $settings['company_address'] ?? 'Jl. Cibiru Tonggoh Bandung (40615)' }}</p>
                        <p>Telp/WA : {{ $settings['company_phone'] ?? '+62 878-7046-1427' }}</p>
                        <p>Email Perusahaan. <a href="mailto:{{ $settings['company_email'] ?? 'hello@vodeco.co.id' }}" class="text-blue-600">{{ $settings['company_email'] ?? 'hello@vodeco.co.id' }}</a></p>
                        <a href="{{ $settings['company_website'] ?? 'https://vodeco.co.id' }}" class="text-blue-600">{{ $settings['company_website_name'] ?? 'Vodeco' }}</a>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Invoice Details -->
        <section class="mt-6 text-xs">
            <div class="space-y-1">
                <p><strong>Inv No :</strong> {{ $invoice->number }}</p>
                <p><strong>Inv Date :</strong> {{ $invoice->issue_date->format('d/m/Y') }}</p>
                <p><strong>Kepada :</strong> {{ $invoice->client_name }}</p>
                @if($invoice->type === InvoiceModel::TYPE_SETTLEMENT && $referenceInvoice)
                    <p><strong>Invoice Acuan :</strong> #{{ $referenceInvoice->number }}</p>
                @endif
            </div>
        </section>

        <!-- Items Table -->
        <main class="mt-6">
            <table class="w-full text-left text-xs border border-gray-300">
                <thead>
                    <tr class="bg-gray-200 border-b border-gray-300">
                        <th class="py-1.5 px-3">Deskripsi produk yang dipesan</th>
                        <th class="py-1.5 px-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                    <tr class="border-b border-gray-300">
                        <td class="px-3 py-2 align-top">{{ $item->description }}</td>
                        <td class="px-3 py-2 text-right align-top">Rp. {{ number_format($item->price * $item->quantity, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach

                    <!-- Spacer -->
                    <tr class="border-b border-gray-300">
                        <td class="px-3 h-5" colspan="2"></td>
                    </tr>

                    <!-- Summary Panel -->
                    <tr class="bg-gray-100 border-t border-gray-300">
                        <td class="px-3 py-3" colspan="2">
                            <div class="flex flex-col items-end text-right text-red-600 space-y-1">
                                <p><strong>Sub Total:</strong> <span class="ml-2">Rp. {{ number_format($invoice->total, 2, ',', '.') }}</span></p>
                                @if($invoice->type !== InvoiceModel::TYPE_SETTLEMENT)
                                    @if(! is_null($invoice->down_payment_due))
                                        <p><strong>Down Payment:</strong> <span class="ml-2">Rp. {{ number_format($invoice->down_payment_due, 2, ',', '.') }}</span></p>
                                    @endif
                                @else
                                    <p><strong>Nominal Pelunasan:</strong> <span class="ml-2">Rp. {{ number_format($invoice->down_payment, 2, ',', '.') }}</span></p>
                                    @if($referenceInvoice)
                                        <p><strong>Sisa Tagihan Sebelum Pelunasan:</strong> <span class="ml-2">Rp. {{ number_format($remainingBeforeSettlement ?? 0, 2, ',', '.') }}</span></p>
                                        <p><strong>Sisa Tagihan Setelah Pelunasan:</strong> <span class="ml-2">Rp. {{ number_format($remainingAfterSettlement ?? 0, 2, ',', '.') }}</span></p>
                                    @endif
                                @endif
                                <p><strong>Status Pembayaran:</strong> <span class="ml-2">{{ $invoice->type === InvoiceModel::TYPE_SETTLEMENT ? $settlementStatusLabel : $paymentStatusLabel }}</span></p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </main>

        <!-- Footer -->
        <footer class="mt-8">
            <p class="text-sm">Terima Kasih telah memberikan kepercayaan kepada kami untuk mengelola perusahaan anda di dunia digital.</p>
            <div class="mt-8">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%; vertical-align: bottom;">
                            <!-- Payment Info -->
                            <div class="p-3 text-xs space-y-3">
                                <p class="italic text-sm mb-3"><strong>Pembayaran melalui transfer :</strong></p>

                                <div class="flex items-start gap-3">
                                    @if($bank1LogoData)
                                        <img src="data:image/{{ $bank1LogoMime }};base64,{{ $bank1LogoData }}" alt="{{ $settings['bank_1_name'] ?? 'Bank' }} Logo" class="h-10 object-contain">
                                    @endif
                                    <div class="space-y-1">
                                        <p class="text-sm tracking-wide uppercase"><strong>{{ $settings['bank_1_name'] ?? 'BCA' }}</strong></p>
                                        <p class="text-[11px] leading-relaxed">{{ $settings['bank_1_account_number'] ?? '3624 500500' }} an. {{ $settings['bank_1_account_name'] ?? 'Vodeco Digital Mediatama' }}</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 mt-4">
                                    @if($bank2LogoData)
                                        <img src="data:image/{{ $bank2LogoMime }};base64,{{ $bank2LogoData }}" alt="{{ $settings['bank_2_name'] ?? 'Bank' }} Logo" class="h-10 object-contain">
                                    @endif
                                    <div class="space-y-1">
                                        <p class="text-sm tracking-wide uppercase"><strong>{{ $settings['bank_2_name'] ?? 'MANDIRI' }}</strong></p>
                                        <p class="text-[11px] leading-relaxed">{{ $settings['bank_2_account_number'] ?? '1390001188113' }} an. {{ $settings['bank_2_account_name'] ?? 'Vodeco Digital Mediatama' }}</p>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="width: 50%; vertical-align: bottom;">
                            <!-- Signature -->
                            <div class="p-3 text-xs text-center space-y-2">
                                <p class="text-sm">{{ $settings['company_city'] ?? 'Bandung' }}, {{ $invoice->issue_date->translatedFormat('d F Y') }}</p>
                                <p class="text-sm"><strong>Pimpinan</strong></p>
                                <div class="text-center">
                                    <img src="data:image/png;base64,{{ $signatureData }}" alt="Signature" class="h-20 w-32 object-contain">
                                </div>
                                <p class="text-sm"><strong>{{ $settings['signature_name'] ?? 'Gibranio Zelmy' }}</strong></p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Note -->
            <div class="mt-12 text-xs italic">
                <p class="font-bold">Note :</p>
                <p>Invoice ini sah jika pembayaran sudah diterima</p>
                <p>Setelah Pembayaran berarti klien sudah menyetujui Service Level Agreement (SLA) berikut ini : Customer DIWAJIBKAN untuk memeriksa dan memastikan semua informasi yang berkaitan dengan warna dan referensi sudah benar sebelum desain mulai diproses. Setelah desain company profile/website selesai dan dikirimkan kepada Customer, perubahan pada warna dan referensi desain tidak dapat dilakukan, kecuali dikenai biaya tambahan.</p>
            </div>
        </footer>
    </div>
</body>

</html>