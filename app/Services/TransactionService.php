<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionService
{
    /**
     * Retrieve paginated transactions with optional filters.
     */
    public function getTransactions(Request $request): LengthAwarePaginator
    {
        $query = Transaction::with('category')->latest('date');

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('type')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('type', $request->type);
            });
        }

        return $query->paginate(10);
    }

    /**
     * Get summary totals for transactions.
     */
    public function getSummary(): array
    {
        $totalPemasukan = Transaction::whereHas('category', function ($q) {
            $q->where('type', 'pemasukan');
        })->sum('amount');

        $totalPengeluaran = Transaction::whereHas('category', function ($q) {
            $q->where('type', 'pengeluaran');
        })->sum('amount');

        return [
            'totalPemasukan' => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'saldo' => $totalPemasukan - $totalPengeluaran,
        ];
    }
}
