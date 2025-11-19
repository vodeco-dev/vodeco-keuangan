<?php

namespace App\Console\Commands;

use App\Services\DebtService;
use Illuminate\Console\Command;

class FixInconsistentDebtStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debts:fix-inconsistent-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix debt statuses that are inconsistent with payment progress';

    protected DebtService $debtService;

    public function __construct(DebtService $debtService)
    {
        parent::__construct();
        $this->debtService = $debtService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing inconsistent debt statuses...');

        $count = $this->debtService->fixInconsistentDebtStatuses();

        $this->info("Successfully fixed {$count} debt status(es).");
    }
}
