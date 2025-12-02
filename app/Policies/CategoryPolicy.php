<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return $user->role === Role::ADMIN || $category->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [Role::ADMIN, Role::ACCOUNTANT]);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->role === Role::ADMIN || $category->user_id === $user->id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->role === Role::ADMIN || $category->user_id === $user->id;
    }
}
