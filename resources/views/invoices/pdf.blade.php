<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
        }
        .header .company-details {
            text-align: left;
        }
        .header .invoice-details {
            text-align: right;
        }
        .company-details h1, .invoice-details h2 {
            margin: 0;
        }
        .client-details {
            margin-bottom: 40px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .items-table th, .items-table td {
            border-bottom: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .items-table th {
            background-color: #f5f5f5;
        }
        .items-table .text-right {
            text-align: right;
        }
        .totals {
            text-align: right;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 0.8em;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-details">
                <h1>{{ $settings['business_name'] ?? 'Nama Bisnis Anda' }}</h1>
                <p>{{ $settings['business_address'] ?? 'Alamat Bisnis Anda' }}</p>
                <p>{{ $settings['business_phone'] ?? '' }}</p>
                <p>{{ $settings['business_email'] ?? '' }}</p>
            </div>
            <div class="invoice-details">
                <h2>INVOICE</h2>
                <p><strong>Nomor:</strong> {{ $invoice->number }}</p>
                <p><strong>Tanggal Terbit:</strong> {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</p>
                <p><strong>Tanggal Jatuh Tempo:</strong> {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : 'N/A' }}</p>
            </div>
        </div>

        <div class="client-details">
            <h3>Ditagihkan kepada:</h3>
            <p><strong>{{ $invoice->client_name }}</strong></p>
            <p>{{ $invoice->client_address }}</p>
            <p>{{ $invoice->client_email }}</p>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th class="text-right">Kuantitas</th>
                    <th class="text-right">Harga Satuan</th>
                    <th class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">Rp {{ number_format($item->price, 2, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($item->quantity * $item->price, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <h3>Total: Rp {{ number_format($invoice->total, 2, ',', '.') }}</h3>
        </div>

        <div class="footer">
            <p>Terima kasih atas pembayaran Anda.</p>
        </div>
    </div>
</body>
</html>
