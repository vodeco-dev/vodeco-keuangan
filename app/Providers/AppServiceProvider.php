<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use App\Models\Transaction;
use App\Policies\TransactionPolicy;
use App\Models\Debt;
use App\Policies\DebtPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Hanya paksa HTTPS jika environment adalah 'production' dan tidak berjalan di konsol
        if ($this->app->environment('production') && !$this->app->runningInConsole()) {
            URL::forceScheme('https');
        }

        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Debt::class, DebtPolicy::class);
    }
}

