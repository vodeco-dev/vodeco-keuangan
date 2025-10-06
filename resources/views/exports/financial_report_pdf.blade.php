@php
    $watermarkPath = public_path('image2.png');
    $watermarkExtension = strtolower(pathinfo($watermarkPath, PATHINFO_EXTENSION));
    if ($watermarkExtension === 'jpg') {
        $watermarkExtension = 'jpeg';
    }
    $watermarkData = is_file($watermarkPath) ? base64_encode(file_get_contents($watermarkPath)) : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan</title>
    @include('exports.partials.financial_report_styles')
    <style>
        body {
            position: relative;
        }

        @if ($watermarkData)
        body::before {
            content: "";
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
            height: 100%;
            background-image: url('data:image/{{ $watermarkExtension }};base64,{{ $watermarkData }}');
            background-position: center;
            background-repeat: no-repeat;
            background-size: contain;
            opacity: 0.08;
        }
        @endif

        .report-wrapper {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="report-wrapper">
        <h1>Laporan Keuangan</h1>
        <p class="report-period">Periode: {{ \Carbon\Carbon::parse($period['start'])->isoFormat('D MMMM YYYY') }} - {{ \Carbon\Carbon::parse($period['end'])->isoFormat('D MMMM YYYY') }}</p>
        @include('exports.partials.financial_report_tables', [
            'incomeTransactions' => $incomeTransactions,
            'expenseTransactions' => $expenseTransactions,
            'debts' => $debts,
            'totals' => $totals ?? [],
        ])
    </div>
</body>
</html>
