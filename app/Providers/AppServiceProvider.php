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
        // Force HTTPS scheme in local environment for ngrok/mkcert.
        if ($this->app->environment('local')) {
            URL::forceScheme('https');
        }

        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Debt::class, DebtPolicy::class);
    }
}

