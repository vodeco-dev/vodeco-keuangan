<?php

namespace App\Providers;

use App\Models\Debt;
use App\Models\Transaction;
use App\Policies\DebtPolicy;
use App\Policies\TransactionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
            // URL::forceScheme('https');
        }

        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Debt::class, DebtPolicy::class);
        Schema::defaultStringLength(191);

        RateLimiter::for('invoice-settlement', function (Request $request) {
            $token = (string) $request->route('token');

            return Limit::perMinute(5)->by($request->ip().'|'.$token);
        });

        RateLimiter::for('invoice-passphrase', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}

