<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\ServiceCost;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
     * Hapus cache ringkasan transaksi untuk pengguna tertentu.
     */
    public function clearSummaryCacheForUser(User $user): void
    {
        Cache::forget('transaction_summary_for_user_' . $user->id);
    }

    /**
     * Hapus cache Agency Gross Income untuk pengguna tertentu.
     */
    public function clearAgencyGrossIncomeCacheForUser(User $user): void
    {
        Cache::forget('agency_gross_income_for_user_' . $user->id);
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

    /**
     * Hitung Agency Gross Income (AGI) untuk pengguna.
     * AGI = total pemasukan - (pass-through + down payment).
     */
    public function getAgencyGrossIncome(User $user): float
    {
        $cacheKey = 'agency_gross_income_for_user_' . $user->id;

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $summary = Transaction::query()
                ->join('categories', 'transactions.category_id', '=', 'categories.id')
                ->leftJoin('service_costs', 'transactions.service_cost_id', '=', 'service_costs.id')
                ->where('transactions.user_id', $user->id)
                ->selectRaw(
                    "SUM(CASE WHEN categories.type = 'pemasukan' THEN transactions.amount ELSE 0 END) AS total_pemasukan," .
                    " SUM(CASE WHEN categories.type = 'pemasukan' AND service_costs.slug IN (?, ?) THEN transactions.amount ELSE 0 END) AS total_potongan",
                    [ServiceCost::PASS_THROUGH_SLUG, ServiceCost::DOWN_PAYMENT_SLUG]
                )
                ->first();

            $totalPemasukan = $summary->total_pemasukan ?? 0;
            $totalPotongan = $summary->total_potongan ?? 0;

            return $totalPemasukan - $totalPotongan;
        });
    }

    /**
     * Siapkan data untuk chart pemasukan dan pengeluaran.
     */
    public function prepareChartData(User $user, $startDate, $endDate): array
    {
        $pemasukanData = Transaction::where('user_id', $user->id)
            ->whereHas('category', function ($query) {
                $query->where('type', 'pemasukan');
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw('DATE(date) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        $pengeluaranData = Transaction::where('user_id', $user->id)
            ->whereHas('category', function ($query) {
                $query->where('type', 'pengeluaran');
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw('DATE(date) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        $dates = collect();
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);

        while ($currentDate <= $lastDate) {
            $dates->push($currentDate->toDateString());
            $currentDate->addDay();
        }

        return [
            'labels' => $dates->map(fn ($date) => Carbon::parse($date)->format('d M')),
            'pemasukan' => $dates->map(fn ($date) => $pemasukanData[$date] ?? 0),
            'pengeluaran' => $dates->map(fn ($date) => $pengeluaranData[$date] ?? 0),
        ];
    }
}

