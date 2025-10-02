<?php

namespace App\Services;

use App\Models\Transaction;
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
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('date', [$start, $end]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        } elseif ($request->filled('date')) {
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
        if (!$request->filled('start_date') && !$request->filled('end_date')) {
            if ($request->filled('month')) {
                $query->whereMonth('date', $request->month);
            }
            if ($request->filled('year')) {
                $query->whereYear('date', $request->year);
            }
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
     * Ambil semua transaksi dengan filter dan paginasi.
     */
    public function getAllTransactions(Request $request)
    {
        $query = Transaction::with(['category', 'user'])->latest('date');

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('date', [$start, $end]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        } elseif ($request->filled('date')) {
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
        if (!$request->filled('start_date') && !$request->filled('end_date')) {
            if ($request->filled('month')) {
                $query->whereMonth('date', $request->month);
            }
            if ($request->filled('year')) {
                $query->whereYear('date', $request->year);
            }
        }

        return $query->paginate(10);
    }

    /**
     * Hapus cache ringkasan semua transaksi.
     */
    public function clearAllSummaryCache(): void
    {
        Cache::forget('transaction_summary_for_all');
    }

    /**
     * Ambil ringkasan keuangan untuk semua transaksi.
     */
    public function getAllSummary(?Request $request = null): array
    {
        $request = $request ?? new Request();

        $query = Transaction::query()
            ->join('categories', 'transactions.category_id', '=', 'categories.id');

        $useFilters = false;

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('transactions.date', [$start, $end]);
            $useFilters = true;
        } elseif ($request->filled('start_date')) {
            $query->whereDate('transactions.date', '>=', $request->start_date);
            $useFilters = true;
        } elseif ($request->filled('end_date')) {
            $query->whereDate('transactions.date', '<=', $request->end_date);
            $useFilters = true;
        }

        if (!$request->filled('start_date') && !$request->filled('end_date')) {
            if ($request->filled('month')) {
                $query->whereMonth('transactions.date', $request->month);
                $useFilters = true;
            }
            if ($request->filled('year')) {
                $query->whereYear('transactions.date', $request->year);
                $useFilters = true;
            }
        }

        $calculateSummary = function () use ($query) {
            $summary = $query->selectRaw('
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
        };

        if ($useFilters) {
            return $calculateSummary();
        }

        $cacheKey = 'transaction_summary_for_all';

        return Cache::remember($cacheKey, 300, $calculateSummary);
    }

    /**
     * Ambil daftar bulan yang memiliki transaksi.
     */
    public function getAvailableMonths(int $limit = 12)
    {
        $connection = Transaction::query()->getConnection()->getDriverName();

        $query = Transaction::query();

        if ($connection === 'sqlite') {
            $query->selectRaw("CAST(strftime('%Y', date) as integer) as year")
                ->selectRaw("CAST(strftime('%m', date) as integer) as month");
        } else {
            $query->selectRaw('YEAR(date) as year')
                ->selectRaw('MONTH(date) as month');
        }

        return $query
            ->distinct()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $date = Carbon::createFromDate($item->year, $item->month, 1)->locale('id');

                return [
                    'month' => $item->month,
                    'year' => $item->year,
                    'label' => $date->translatedFormat('F Y'),
                ];
            });
    }

    /**
     * Siapkan data untuk chart pemasukan dan pengeluaran.
     */
    public function prepareChartData(?User $user, $startDate, $endDate, ?int $categoryId = null, ?string $type = null): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $baseQuery = Transaction::query()
            ->when($user !== null, function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereBetween('date', [$start, $end]);

        if ($categoryId) {
            $baseQuery->where('category_id', $categoryId);
        }

        $pemasukanData = collect();
        if (!$type || $type === 'pemasukan') {
            $pemasukanQuery = clone $baseQuery;
            $pemasukanData = $pemasukanQuery
                ->whereHas('category', function ($query) {
                    $query->where('type', 'pemasukan');
                })
                ->select(DB::raw('DATE(date) as date'), DB::raw('SUM(amount) as total'))
                ->groupBy('date')
                ->pluck('total', 'date');
        }

        $pengeluaranData = collect();
        if (!$type || $type === 'pengeluaran') {
            $pengeluaranQuery = clone $baseQuery;
            $pengeluaranData = $pengeluaranQuery
                ->whereHas('category', function ($query) {
                    $query->where('type', 'pengeluaran');
                })
                ->select(DB::raw('DATE(date) as date'), DB::raw('SUM(amount) as total'))
                ->groupBy('date')
                ->pluck('total', 'date');
        }

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

