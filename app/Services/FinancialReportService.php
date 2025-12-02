<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Transaction;
use Carbon\Carbon;

class FinancialReportService
{
    public function generate(
        ?int $userId,
        string $startDate,
        string $endDate,
        ?int $categoryId = null,
        ?string $type = null
    ): array {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $transactionQuery = Transaction::with('category')
            ->when($userId !== null, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereBetween('date', [$start, $end]);

        if ($categoryId) {
            $transactionQuery->where('category_id', $categoryId);
        }

        if ($type) {
            $transactionQuery->whereHas('category', function ($query) use ($type) {
                $query->where('type', $type);
            });
        }

        $transactions = $transactionQuery
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $incomeTransactions = $transactions
            ->filter(fn ($transaction) => $transaction->category && $transaction->category->type === 'pemasukan')
            ->values();

        $expenseTransactions = $transactions
            ->filter(fn ($transaction) => $transaction->category && $transaction->category->type === 'pengeluaran')
            ->values();

        $totalIncome = $incomeTransactions->sum('amount');
        $totalExpense = $expenseTransactions->sum('amount');

        $debts = Debt::with('payments')
            ->when($userId !== null, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereBetween('due_date', [$start, $end])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        $totalDebt = $debts->sum('amount');
        $totalPaidDebt = $debts->sum('paid_amount');
        $totalRemainingDebt = $debts->sum('remaining_amount');

        return [
            'transactions' => $transactions,
            'incomeTransactions' => $incomeTransactions,
            'expenseTransactions' => $expenseTransactions,
            'debts' => $debts,
            'totals' => [
                'pemasukan' => $totalIncome,
                'pengeluaran' => $totalExpense,
                'selisih' => $totalIncome - $totalExpense,
                'hutang' => $totalDebt,
                'pembayaranHutang' => $totalPaidDebt,
                'sisaHutang' => $totalRemainingDebt,
            ],
        ];
    }
}
