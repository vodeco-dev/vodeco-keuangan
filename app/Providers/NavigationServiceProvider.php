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
                'name' => 'Cek Konfirmasi Invoice',
                'route' => 'invoices.check-confirmation',
                'icon' => view('components.icons.check-confirmation')->render(),
            ],
            [
                'name' => 'Passphrase Portal',
                'route' => 'invoice-portal.passphrases.index',
                'icon' => view('components.icons.passphrases')->render(),
                'roles' => ['admin', 'accountant'],
            ],
            [
                'name' => 'Laporan',
                'route' => 'reports.index',
                'icon' => view('components.icons.reports')->render(),
            ],
            // Dropdown for Admins
            [
                'name' => 'Riwayat & Log',
                'icon' => view('components.icons.activity_logs')->render(),
                'can' => 'admin',
                'children' => [
                    [
                        'name' => 'Riwayat Permintaan',
                        'route' => 'user-deletion-requests.index',
                        'active_routes' => ['user-deletion-requests.index']
                    ],
                    [
                        'name' => 'Permintaan Hapus',
                        'route' => 'admin.deletion-requests.index',
                        'active_routes' => ['admin.deletion-requests.index']
                    ],
                    [
                        'name' => 'Activity Logs',
                        'route' => 'admin.activity-logs.index',
                        'active_routes' => ['admin.activity-logs.index']
                    ],
                ]
            ],
            // Standalone link for non-admins
            [
                'name' => 'Riwayat Permintaan',
                'route' => 'user-deletion-requests.index',
                'icon' => view('components.icons.activity_logs')->render(),
                'can_not' => 'admin',
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

