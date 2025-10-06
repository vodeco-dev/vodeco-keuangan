<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan</title>
    @include('exports.partials.financial_report_styles')
    <style>
        body {
            padding: 32px 40px;
            background: url("public/image2.png") no-repeat center;
        }

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
