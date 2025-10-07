<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Transaction $transaction): bool
    {
        if (in_array($user->role, [Role::ADMIN, Role::ACCOUNTANT], true)) {
            return true;
        }

        return $transaction->user_id === $user->id;
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->role === Role::ADMIN;
    }
}
