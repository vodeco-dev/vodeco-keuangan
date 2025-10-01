<style>
    :root {
        color-scheme: light;
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        color: #1f2937;
        background-color: #f8fafc;
        margin: 0;
        padding: 24px;
    }

    .report-wrapper {
        max-width: 1200px;
        margin: 0 auto;
    }

    h1 {
        margin-bottom: 4px;
        font-size: 28px;
        color: #000080;
    }

    .report-period {
        margin: 0 0 24px 0;
        color: #475569;
        font-size: 14px;
    }

    .summary-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 16px 12px;
        margin-bottom: 24px;
    }

    .summary-table td {
        background: #eef2ff;
        border: 1px solid rgba(0, 0, 128, 0.25);
        border-radius: 12px;
        padding: 14px 18px;
        vertical-align: top;
    }

    .summary-placeholder {
        background: transparent;
        border: none;
        padding: 0;
    }

    .summary-label {
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 12px;
        color: #1e3a8a;
        margin-bottom: 6px;
    }

    .summary-value {
        font-size: 20px;
        font-weight: 700;
        color: #000080;
    }

    .table-section {
        margin-bottom: 32px;
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid rgba(0, 0, 128, 0.15);
        padding: 20px 24px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
    }

    .section-title {
        display: inline-block;
        background-color: #000080;
        color: #ffffff;
        padding: 10px 18px;
        border-radius: 999px;
        font-size: 16px;
        font-weight: 600;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 18px;
        font-size: 13px;
    }

    .dual-table {
        width: 100%;
        margin-top: 18px;
        border-collapse: separate;
        border-spacing: 20px;
    }

    .dual-table > tbody > tr > td {
        width: 50%;
        vertical-align: top;
    }

    thead th {
        background-color: #000080;
        color: #ffffff;
        padding: 12px 14px;
        text-align: left;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    tbody td {
        padding: 12px 14px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.4);
        color: #1f2937;
        line-height: 1.5;
        vertical-align: top;
    }

    tfoot td {
        padding: 12px 14px;
        font-weight: 700;
        color: #000080;
        background: rgba(0, 0, 128, 0.05);
        border-top: 1px solid rgba(0, 0, 128, 0.15);
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-paid {
        background-color: rgba(16, 185, 129, 0.15);
        color: #047857;
    }

    .status-unpaid {
        background-color: rgba(248, 113, 113, 0.15);
        color: #b91c1c;
    }

    .empty-state {
        padding: 20px;
        text-align: center;
        color: #64748b;
        font-style: italic;
    }
</style>
