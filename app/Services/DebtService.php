<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DebtService
{
    /**
     * Retrieve debts with optional filters and paginate the results.
     */
    public function getDebts(Request $request, User $user): LengthAwarePaginator
    {
        $query = Debt::with(['payments', 'category'])
            ->where('user_id', $user->id)
            ->where('type', Debt::TYPE_DOWN_PAYMENT)
            ->latest();

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('related_party', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('due_date_from')) {
            $query->whereDate('due_date', '>=', $request->due_date_from);
        }

        if ($request->filled('due_date_to')) {
            $query->whereDate('due_date', '<=', $request->due_date_to);
        }

        return $query->paginate();
    }

    /**
     * Calculate summary amounts for debts.
     */
    public function getSummary(User $user): array
    {
        $totalDownPayment = Debt::where('user_id', $user->id)
            ->where('type', Debt::TYPE_DOWN_PAYMENT)
            ->sum('amount');

        $totalBelumLunas = Debt::where('user_id', $user->id)
            ->where('type', Debt::TYPE_DOWN_PAYMENT)
            ->where('status', Debt::STATUS_BELUM_LUNAS)
            ->get()
            ->sum('remaining_amount');

        $totalLunas = Debt::where('user_id', $user->id)
            ->where('type', Debt::TYPE_DOWN_PAYMENT)
            ->where('status', Debt::STATUS_LUNAS)
            ->sum('amount');

        return [
            'totalDownPayment' => $totalDownPayment,
            'totalBelumLunas' => $totalBelumLunas,
            'totalLunas' => $totalLunas,
        ];
    }
}
