<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::share('navigation', [
            [
                'name' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => view('components.icons.dashboard')->render(),
            ],
            [
                'name' => 'Transaksi',
                'route' => 'transactions.index',
                'icon' => view('components.icons.transactions')->render(),
            ],
            [
                'name' => 'Kategori',
                'route' => 'categories.index',
                'icon' => view('components.icons.categories')->render(),
            ],
            [
                'name' => 'Service Cost',
                'route' => 'service_costs.index',
                'icon' => view('components.icons.service_costs')->render(),
                'can' => 'admin',
            ],
            [
                'name' => 'Hutang',
                'route' => 'debts.index',
                'icon' => view('components.icons.debts')->render(),
            ],
            [
                'name' => 'Invoice',
                'route' => 'invoices.index',
                'icon' => view('components.icons.invoices')->render(),
            ],
            [
                'name' => 'Pendapatan Berulang',
                'route' => 'recurring_revenues.index',
                'icon' => view('components.icons.recurring_revenues')->render(),
            ],
            [
                'name' => 'Laporan',
                'route' => 'reports.index',
                'icon' => view('components.icons.reports')->render(),
            ],
            [
                'name' => 'Users',
                'route' => 'users.index',
                'icon' => view('components.icons.users')->render(),
                'can' => 'admin',
            ],
        ]);
    }
}

