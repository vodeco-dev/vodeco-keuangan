<?php

namespace App\Console\Commands;

use App\Services\DebtService;
use Illuminate\Console\Command;

class FixInconsistentDebtStatuses extends Command
{
    protected $signature = 'debts:fix-inconsistent-statuses';

    protected $description = 'Fix debt statuses that are inconsistent with payment progress';

    protected DebtService $debtService;

    public function __construct(DebtService $debtService)
    {
        parent::__construct();
        $this->debtService = $debtService;
    }

    public function handle()
    {
        $this->info('Fixing inconsistent debt statuses...');

        $count = $this->debtService->fixInconsistentDebtStatuses();

        $this->info("Successfully fixed {$count} debt status(es).");
    }
}
