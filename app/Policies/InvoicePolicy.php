<?php

namespace App\Policies;

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
        return $invoice->recurringRevenue && $invoice->recurringRevenue->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $invoice->recurringRevenue && $invoice->recurringRevenue->user_id === $user->id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $invoice->recurringRevenue && $invoice->recurringRevenue->user_id === $user->id;
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $user->is_admin || ($invoice->recurringRevenue && $invoice->recurringRevenue->user_id === $user->id);
    }

    public function markPaid(User $user, Invoice $invoice): bool
    {
        return $user->is_admin || ($invoice->recurringRevenue && $invoice->recurringRevenue->user_id === $user->id);
    }
}
