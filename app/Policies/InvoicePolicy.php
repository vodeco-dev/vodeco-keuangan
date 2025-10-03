<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id || $user->role === Role::ADMIN;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id || $user->role === Role::ADMIN;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id || $user->role === Role::ADMIN;
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    public function storePayment(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->user_id
            || in_array($user->role, [Role::ADMIN, Role::ACCOUNTANT], true);
    }

    public function viewDownPaymentTab(User $user): bool
    {
        return in_array($user->role, [Role::ADMIN, Role::ACCOUNTANT, Role::STAFF], true);
    }

    public function viewPayInFullTab(User $user): bool
    {
        return in_array($user->role, [Role::ADMIN, Role::ACCOUNTANT, Role::STAFF], true);
    }

    public function viewSettlementTab(User $user): bool
    {
        return in_array($user->role, [Role::ADMIN, Role::ACCOUNTANT], true);
    }
}
