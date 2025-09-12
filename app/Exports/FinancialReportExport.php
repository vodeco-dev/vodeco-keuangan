<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Models\Transaction;
use App\Models\Debt;

class FinancialReportExport implements FromView, WithTitle
{
    protected int $userId;
    protected string $startDate;
    protected string $endDate;

    public function __construct(int $userId, string $startDate, string $endDate)
    {
        $this->userId = $userId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        $transactions = Transaction::with('category')
            ->where('user_id', $this->userId)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date', 'asc')
            ->get();

        $debts = Debt::with('payments')
            ->where('user_id', $this->userId)
            ->whereBetween('due_date', [$this->startDate, $this->endDate])
            ->orderBy('due_date', 'asc')
            ->get();

        return view('exports.financial_report', [
            'transactions' => $transactions,
            'debts' => $debts
        ]);
    }

    public function title(): string
    {
        return 'Laporan Keuangan';
    }
}
