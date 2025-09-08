<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping
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

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Transaction::with('category')
            ->where('user_id', $this->userId)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Kategori',
            'Tipe',
            'Deskripsi',
            'Jumlah',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->date,
            $transaction->category->name,
            ucfirst($transaction->category->type),
            $transaction->description,
            $transaction->amount,
        ];
    }
}
