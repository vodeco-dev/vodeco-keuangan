<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Models\Transaction;
use App\Models\Debt;

class FinancialReportExport implements FromView, WithTitle
{
    protected ?int $userId;
    protected string $startDate;
    protected string $endDate;
    protected ?int $categoryId;
    protected ?string $type;

    public function __construct(?int $userId, string $startDate, string $endDate, ?int $categoryId = null, ?string $type = null)
    {
        $this->userId = $userId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->categoryId = $categoryId;
        $this->type = $type;
    }

    public function view(): View
    {
        $transactionQuery = Transaction::with('category')
            ->when($this->userId !== null, function ($query) {
                $query->where('user_id', $this->userId);
            })
            ->whereBetween('date', [$this->startDate, $this->endDate]);

        if ($this->categoryId) {
            $transactionQuery->where('category_id', $this->categoryId);
        }

        if ($this->type) {
            $transactionQuery->whereHas('category', function ($query) {
                $query->where('type', $this->type);
            });
        }

        $transactions = $transactionQuery
            ->orderBy('date', 'asc')
            ->get();

        $debts = Debt::with('payments')
            ->when($this->userId !== null, function ($query) {
                $query->where('user_id', $this->userId);
            })
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
