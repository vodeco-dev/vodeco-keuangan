<?php

namespace App\Exports;

use App\Services\FinancialReportService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

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
        $reportData = app(FinancialReportService::class)->generate(
            $this->userId,
            $this->startDate,
            $this->endDate,
            $this->categoryId,
            $this->type
        );

        return view('exports.financial_report', [
            ...$reportData,
            'period' => [
                'start' => $this->startDate,
                'end' => $this->endDate,
            ],
        ]);
    }

    public function title(): string
    {
        return 'Laporan Keuangan';
    }

}
