<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Menjalankan proses untuk mengirim pengingat invoice yang jatuh tempo
        $schedule->command('invoices:reminder')->daily();

        // Cleanup expired PDF cache files (runs every hour)
        $schedule->command('pdf:cleanup-cache')
            ->hourly()
            ->when(fn () => config('pdf.cache.enabled', true));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
