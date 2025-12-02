<?php

namespace App\Console\Commands;

use App\Services\DebtService;
use Illuminate\Console\Command;

class SyncCompletedDebtsToTransactions extends Command
{
    protected $signature = 'debts:sync-completed-to-transactions';

    protected $description = 'Sync completed debts (100% progress) to transactions';

    protected DebtService $debtService;

    public function __construct(DebtService $debtService)
    {
        parent::__construct();
        $this->debtService = $debtService;
    }

    public function handle()
    {
        $this->info('Syncing completed debts to transactions...');

        $count = $this->debtService->syncCompletedDebtsToTransactions();

        $this->info("Successfully created {$count} transaction record(s) from completed debts.");
    }
}
