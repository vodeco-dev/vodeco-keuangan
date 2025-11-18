<?php

namespace App\Console\Commands;

use App\Services\DebtService;
use Illuminate\Console\Command;

class SyncMissingDebts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debts:sync-missing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync missing debt records for invoices that should have debts';

    /**
     * Execute the console command.
     */
    public function handle(DebtService $debtService): int
    {
        $this->info('Syncing missing debts...');

        $count = $debtService->syncMissingDebts();

        if ($count > 0) {
            $this->info("Successfully created {$count} debt record(s).");
        } else {
            $this->info('No missing debts found.');
        }

        return Command::SUCCESS;
    }
}

