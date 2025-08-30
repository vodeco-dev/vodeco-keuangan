<?php

namespace App\Services;

use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DebtService
{
    /**
     * Retrieve debts with optional filters.
     */
    public function getDebts(Request $request): Collection
    {
        $query = Debt::with('payments')->latest();

        if ($request->filled('type_filter')) {
            $query->where('type', $request->type_filter);
        }

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('related_party', 'like', '%' . $request->search . '%');
            });
        }

        return $query->get();
    }

    /**
     * Calculate summary amounts for debts.
     */
    public function getSummary(Collection $debts): array
    {
        $totalPassThrough = Debt::where('type', 'pass_through')->sum('amount');
        $totalDownPayment = Debt::where('type', 'down_payment')->sum('amount');
        $totalBelumLunas = $debts->where('status', 'belum lunas')->sum('remaining_amount');
        $totalLunas = Debt::where('status', 'lunas')->sum('amount');

        return [
            'totalPassThrough' => $totalPassThrough,
            'totalDownPayment' => $totalDownPayment,
            'totalBelumLunas' => $totalBelumLunas,
            'totalLunas' => $totalLunas,
        ];
    }
}
