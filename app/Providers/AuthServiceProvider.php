<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Transaction;
use App\Policies\TransactionPolicy;
use App\Models\Debt;
use App\Policies\DebtPolicy;
use App\Models\Category;
use App\Policies\CategoryPolicy;
use App\Models\Invoice;
use App\Policies\InvoicePolicy;
use App\Policies\UserPolicy;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{   
    protected $policies = [
        Transaction::class => TransactionPolicy::class,
        Debt::class => DebtPolicy::class,
        Category::class => CategoryPolicy::class,
        Invoice::class => InvoicePolicy::class,
        User::class => UserPolicy::class,
    ];

    public function register(): void
    {
    }

    public function boot(): void
    {
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Debt::class, DebtPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
