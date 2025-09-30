<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Debt;
use App\Models\User;

class DebtPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Debt $debt): bool
    {
        return $debt->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Debt $debt): bool
    {
        if ($user->role === Role::ADMIN) {
            return true;
        }

        return $debt->user_id === $user->id;
    }

    public function delete(User $user, Debt $debt): bool
    {
        return $debt->user_id === $user->id;
    }
}
