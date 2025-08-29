<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TransactionService
{
    /**
     * Ambil transaksi dengan filter dan paginasi untuk pengguna tertentu.
     */
    public function getTransactionsForUser(User $user, Request $request)
    {
        $query = Transaction::with('category')
            ->where('user_id', $user->id) // KEAMANAN: Filter berdasarkan user
            ->latest('date');

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
     * Ambil ringkasan keuangan untuk pengguna tertentu menggunakan satu query yang efisien dan caching.
     * INI ADALAH VERSI GABUNGAN YANG SUDAH DISEMPURNAKAN.
     */
    public function getSummaryForUser(User $user): array
    {
        // Cache key dibuat unik untuk setiap pengguna
        $cacheKey = 'transaction_summary_for_user_' . $user->id;

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $summary = Transaction::query()
                ->join('categories', 'transactions.category_id', '=', 'categories.id')
                ->where('transactions.user_id', $user->id) // KEAMANAN: Filter data milik user
                ->selectRaw('
                    SUM(CASE WHEN categories.type = "pemasukan" THEN transactions.amount ELSE 0 END) AS totalPemasukan,
                    SUM(CASE WHEN categories.type = "pengeluaran" THEN transactions.amount ELSE 0 END) AS totalPengeluaran
                ')
                ->first();

            $pemasukan = $summary->totalPemasukan ?? 0;
            $pengeluaran = $summary->totalPengeluaran ?? 0;

            return [
                'totalPemasukan'   => $pemasukan,
                'totalPengeluaran' => $pengeluaran,
                'saldo'            => $pemasukan - $pengeluaran,
            ];
        });
    }
}

