<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
    body{
        font-family: Arial, sans-serif;
        font-size: 12px;
    }
    .container{
        width: 100%;
        margin: 0 auto;
    }
    .header-left{
        float: left;
        width: 50%;
    }
    .header-right{
        float: right;
        width: 50%;
        text-align: right;
    }
    .invoice-details{
        clear: both;
        margin-top: 20px;
    }
    .invoice-details table{
        width: 100%;
        border-collapse: collapse;
    }
    .invoice-details th, .invoice-details td{
        border: 1px solid #ccc;
        padding: 8px;
    }
    .invoice-details th{
        background-color: #f2f2f2;
    }
    .footer{
        margin-top: 20px;
    }
    .bank-details{
        float: left;
        width: 50%;
    }
    .signature{
        float: right;
        width: 50%;
        text-align: center;
    }
</style>
</head>
<body>
    <div class="container">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td style="width:40%; vertical-align: top;">
                    <img src="{{ public_path('image4.png') }}" style="width: 150px;">
                </td>
                <td style="width:60%; text-align: right; vertical-align: top;">
                    <img src="{{ public_path('image1.png') }}" style="width: 200px;">
                    <p style="margin: 0; font-size: 11pt;">&copy; {{ $settings['business_name'] ?? 'CV. Vodeco Digital Mediatama' }}</p>
                    <p style="margin: 0; font-size: 9pt;">Kantor Pusat: {{ $settings['business_address'] ?? 'Jl. Cibiru Tonggoh Bandung (40615)' }} Telp/WA : {{ $settings['business_phone'] ?? '+62 878-7046-1427' }}</p>
                    <p style="margin: 0; font-size: 9pt;">Email Perusahaan. <a href="mailto:{{ $settings['business_email'] ?? 'hello@vodeco.co.id' }}">{{ $settings['business_email'] ?? 'hello@vodeco.co.id' }}</a> <a href="https://vodeco.co.id">Vodeco</a></p>
                </td>
            </tr>
        </table>

        <div style="margin-top: 20px;">
            <p style="font-size: 12pt;">Inv No &nbsp; &nbsp;: {{ $invoice->number }}</p>
            <p style="font-size: 12pt;">Inv Date : {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</p>
        </div>

        <div style="margin-top: 20px;">
            <p>Kepada : {{ $invoice->client_name }}</p>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-top:20px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #ffffff; background-color:#eeeeee; padding: 5px; text-align: left;">Perpanjang google ads <a href="https://ihatec-mr.com/">https://ihatec-mr.com/</a> 30 hari (jasa maintenance)</th>
                    <th style="border: 1px solid #ffffff; background-color:#eeeeee; padding: 5px; text-align: center;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td style="border: 1px solid #ffffff; background-color:#eeeeee; padding: 5px;">{{ $item->description }}</td>
                    <td style="border: 1px solid #ffffff; padding: 5px; text-align: right;">Rp. {{ number_format($item->price * $item->quantity, 2, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr>
                    <td style="border: 1px solid #ffffff; background-color:#eeeeee; text-align: right; padding: 5px;"><strong>Sub Total</strong></td>
                    <td style="border: 1px solid #ffffff; padding: 5px; text-align: right;"><strong>Rp. {{ number_format($invoice->total, 2, ',', '.') }}</strong></td>
                </tr>
                 <tr>
                    <td style="border: 1px solid #ffffff; background-color:#eeeeee; text-align: right; padding: 5px; color: red;">pembayaran</td>
                    <td style="border: 1px solid #ffffff; padding: 5px; text-align: right; color: red;">Rp {{ number_format($invoice->total, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #ffffff; background-color:#eeeeee; text-align: right; padding: 5px;"><strong>Keterangan</strong></td>
                    <td style="border: 1px solid #ffffff; padding: 5px; text-align: center; color: red;">
                        @if($invoice->status != 'Paid')
                            menunggu pembayaran
                        @else
                            Lunas
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <p style="font-size: 10pt; margin-top: 20px;">Terima kasih telah memberikan kepercayaan kepada kami untuk mendesain dan mengelola jasa website Anda sebagai sarana digital marketing di media online.</p>

        <table style="width:100%; margin-top: 20px;">
            <tr>
                <td style="width: 50%;">
                    <p><em>Pembayaran melalui transfer :</em></p>
                    <p><strong>BCA</strong></p>
                    <img src="{{ public_path('image3.png') }}" style="width: 100px;">
                    <p><strong>3624 500500</strong> an. Vodeco Digital Mediatama</p>
                    <p><strong>MANDIRI</strong></p>
                    <img src="{{ public_path('image2.png') }}" style="width: 100px;">
                    <p><strong>1390001188113</strong> an. Vodeco Digital Mediatama</p>
                </td>
                <td style="width: 50%; text-align: center; vertical-align: top;">
                    <p>Bandung, {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d F Y') }}</p>
                    <p><strong>Pimpinan</strong></p>
                    <br><br><br>
                    <p><strong>Gibranio Zelmy</strong></p>
                </td>
            </tr>
        </table>
        <div style="margin-top: 30px;">
            <p style="font-style: italic; font-size: 8pt;">Note :</p>
            <p style="font-style: italic; font-size: 8pt;">Invoice ini sah jika pembayaran sudah diterima</p>
            <p style-="font-style: italic; font-size: 8pt;">Setelah Pembayaran berarti klien sudah menyetujui Service Level Agreement (SLA) berikut ini : Customer DIWAJIBKAN untuk memeriksa dan memastikan semua informasi yang berkaitan dengan warna dan referensi sudah benar sebelum desain mulai diproses. Setelah desain company profile/website selesai dan dikirimkan kepada Customer, perubahan pada warna dan referensi desain tidak dapat dilakukan, kecuali dikenai biaya tambahan.</p>
        </div>
    </div>
</body>
</html>
