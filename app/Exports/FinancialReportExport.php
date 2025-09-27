<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\Transaction;
use App\Models\Debt;

class FinancialReportExport implements FromView, WithTitle, WithEvents
{
    protected ?int $userId;
    protected string $startDate;
    protected string $endDate;
    protected ?int $categoryId;
    protected ?string $type;
    protected int $transactionCount = 0;
    protected int $debtCount = 0;

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

        $this->transactionCount = $transactions->count();

        $debts = Debt::with('payments')
            ->when($this->userId !== null, function ($query) {
                $query->where('user_id', $this->userId);
            })
            ->whereBetween('due_date', [$this->startDate, $this->endDate])
            ->orderBy('due_date', 'asc')
            ->get();

        $this->debtCount = $debts->count();

        return view('exports.financial_report', [
            'transactions' => $transactions,
            'debts' => $debts
        ]);
    }

    public function title(): string
    {
        return 'Laporan Keuangan';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $debtHeaderRow = null;
                $highestRow = $sheet->getHighestRow();

                for ($row = 1; $row <= $highestRow; $row++) {
                    if ($sheet->getCell('A' . $row)->getValue() === 'Pihak Terkait') {
                        $debtHeaderRow = $row;
                        break;
                    }
                }

                if ($debtHeaderRow === null) {
                    return;
                }

                $sheet->insertNewRowBefore($debtHeaderRow, 1);
                $transactionSummaryRow = $debtHeaderRow;
                $debtHeaderRow += 1;

                $transactionDataStartRow = 2;

                $sheet->setCellValue('D' . $transactionSummaryRow, 'Total');

                if ($this->transactionCount > 0) {
                    $sheet->setCellValue(
                        'E' . $transactionSummaryRow,
                        sprintf('=SUM(E%d:E%d)', $transactionDataStartRow, $transactionDataStartRow + $this->transactionCount - 1)
                    );
                } else {
                    $sheet->setCellValue('E' . $transactionSummaryRow, 0);
                }

                $debtDataStartRow = $debtHeaderRow + 1;
                $debtSummaryRow = $debtDataStartRow + $this->debtCount;

                $sheet->setCellValue('A' . $debtSummaryRow, 'Total');

                if ($this->debtCount > 0) {
                    $debtDataEndRow = $debtDataStartRow + $this->debtCount - 1;
                    $sheet->setCellValue(
                        'C' . $debtSummaryRow,
                        sprintf('=SUM(C%d:C%d)', $debtDataStartRow, $debtDataEndRow)
                    );
                    $sheet->setCellValue(
                        'D' . $debtSummaryRow,
                        sprintf('=SUM(D%d:D%d)', $debtDataStartRow, $debtDataEndRow)
                    );
                    $sheet->setCellValue(
                        'E' . $debtSummaryRow,
                        sprintf('=SUM(E%d:E%d)', $debtDataStartRow, $debtDataEndRow)
                    );
                } else {
                    $sheet->setCellValue('C' . $debtSummaryRow, 0);
                    $sheet->setCellValue('D' . $debtSummaryRow, 0);
                    $sheet->setCellValue('E' . $debtSummaryRow, 0);
                }
            }
        ];
    }
}
