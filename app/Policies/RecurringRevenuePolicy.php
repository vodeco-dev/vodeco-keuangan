<?php

namespace App\Policies;

use App\Models\RecurringRevenue;
use App\Models\User;

class RecurringRevenuePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RecurringRevenue $recurringRevenue): bool
    {
        return $recurringRevenue->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, RecurringRevenue $recurringRevenue): bool
    {
        return $recurringRevenue->user_id === $user->id;
    }

    public function delete(User $user, RecurringRevenue $recurringRevenue): bool
    {
        return $recurringRevenue->user_id === $user->id;
    }
}
