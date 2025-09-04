<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DebtService
{
    /**
     * Retrieve debts with optional filters.
     */
    public function getDebts(Request $request, User $user): Collection
    {
        $query = Debt::with('payments')
            ->where('user_id', $user->id)
            ->latest();

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
    public function getSummary(User $user): array
    {
        $totalDownPayment = Debt::where('user_id', $user->id)
            ->where('type', Debt::TYPE_DOWN_PAYMENT)
            ->sum('amount');

        $totalPassThrough = Debt::where('user_id', $user->id)
            ->where('type', Debt::TYPE_PASS_THROUGH)
            ->sum('amount');

        $totalBelumLunas = Debt::where('user_id', $user->id)
            ->where('status', 'belum lunas')
            ->get()
            ->sum('remaining_amount');

        $totalLunas = Debt::where('user_id', $user->id)
            ->where('status', 'lunas')
            ->sum('amount');

        return [
            'totalPassThrough' => $totalPassThrough,
            'totalDownPayment' => $totalDownPayment,
            'totalBelumLunas' => $totalBelumLunas,
            'totalLunas' => $totalLunas,
        ];
    }
}
