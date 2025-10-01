@php
    $manifestPath = public_path('build/manifest.json');
    $manifest = is_file($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : null;
    $cssFile = $manifest['resources/css/app.css']['file'] ?? null;
    $cssPath = $cssFile ? public_path('build/' . $cssFile) : null;
    $cssContent = $cssPath && is_file($cssPath) ? file_get_contents($cssPath) : '';
    $logoPath = public_path($settings['company_logo'] ?? 'vodeco.webp');
    $logoData = base64_encode(file_get_contents($logoPath));
    $signaturePath = public_path($settings['signature_image'] ?? 'image3.png');
    $signatureData = base64_encode(file_get_contents($signaturePath));
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
            margin: 20mm 18mm;
        }
        body {
            font-family: 'Arial', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 text-sm">
    <div class="mx-auto max-w-[210mm] bg-white px-10 py-8">
        <!-- Header -->
        <header class="grid grid-cols-2 gap-6 items-start">
            <div>
                <img src="data:image/webp;base64,{{ $logoData }}" alt="Vodeco Logo" class="w-48">
            </div>
            <div class="bg-gray-100 rounded-lg p-4 text-right text-xs space-y-1">
                <h1 class="font-bold text-base">&copy; {{ $settings['company_name'] ?? 'CV. Vodeco Digital Mediatama' }}</h1>
                <p>{{ $settings['company_address'] ?? 'Jl. Cibiru Tonggoh Bandung (40615)' }}</p>
                <p>Telp/WA : {{ $settings['company_phone'] ?? '+62 878-7046-1427' }}</p>
                <p>Email Perusahaan. <a href="mailto:{{ $settings['company_email'] ?? 'hello@vodeco.co.id' }}" class="text-blue-600">{{ $settings['company_email'] ?? 'hello@vodeco.co.id' }}</a></p>
                <a href="{{ $settings['company_website'] ?? 'https://vodeco.co.id' }}" class="text-blue-600">{{ $settings['company_website_name'] ?? 'Vodeco' }}</a>
            </div>
        </header>

        <!-- Invoice Details -->
        <section class="mt-6 text-xs">
            <div class="space-y-1">
                <p><span class="font-semibold">Inv No :</span> {{ $invoice->number }}</p>
                <p><span class="font-semibold">Inv Date :</span> {{ $invoice->issue_date->format('d/m/Y') }}</p>
                <p><span class="font-semibold">Kepada :</span> {{ $invoice->client_name }}</p>
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
                                <p class="font-semibold">Sub Total: <span class="ml-2">Rp. {{ number_format($invoice->total, 2, ',', '.') }}</span></p>
                                <p class="font-semibold">Down Payment: <span class="ml-2">Rp. {{ number_format($invoice->down_payment, 2, ',', '.') }}</span></p>
                                <p class="font-semibold">Keterangan: <span class="ml-2">{{ $invoice->status ?? 'Menunggu Pembayaran' }}</span></p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </main>

        <!-- Footer -->
        <footer class="mt-8">
            <p class="text-sm">Terima kasih telah memberikan kepercayaan kepada kami untuk mendesain dan mengelola jasa website Anda sebagai sarana digital marketing di media online.</p>
            <div class="flex justify-between mt-8">
                <!-- Payment Info -->
                <div class="w-1/2">
                    <p class="font-bold italic">Pembayaran melalui transfer :</p>
                    <div class="mt-4">
                        <p class="font-bold">{{ $settings['bank_1_name'] ?? 'BCA' }}</p>
                        <p>{{ $settings['bank_1_account_number'] ?? '3624 500500' }} an. {{ $settings['bank_1_account_name'] ?? 'Vodeco Digital Mediatama' }}</p>
                    </div>
                    <div class="mt-4">
                        <p class="font-bold">{{ $settings['bank_2_name'] ?? 'MANDIRI' }}</p>
                        <p>{{ $settings['bank_2_account_number'] ?? '1390001188113' }} an. {{ $settings['bank_2_account_name'] ?? 'Vodeco Digital Mediatama' }}</p>
                    </div>
                </div>
                <!-- Signature -->
                <div class="w-1/2 text-center">
                    <p>{{ $settings['company_city'] ?? 'Bandung' }}, {{ $invoice->issue_date->translatedFormat('d F Y') }}</p>
                    <p class="font-bold">Pimpinan</p>
                    <div class="h-20 w-32 mx-auto my-2">
                        <img src="data:image/png;base64,{{ $signatureData }}" alt="Signature" class="h-full w-full object-contain">
                    </div>
                    <p class="font-bold">{{ $settings['signature_name'] ?? 'Gibranio Zelmy' }}</p>
                </div>
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