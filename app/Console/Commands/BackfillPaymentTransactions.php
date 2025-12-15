<?php

namespace App\Console\Commands;

use App\Services\DebtService;
use App\Services\TransactionService;
use Illuminate\Console\Command;

class BackfillPaymentTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debts:backfill-payment-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill transaksi untuk pembayaran yang belum memiliki transaksi terkait';

    protected DebtService $debtService;
    protected TransactionService $transactionService;

    /**
     * Create a new command instance.
     */
    public function __construct(DebtService $debtService, TransactionService $transactionService)
    {
        parent::__construct();
        $this->debtService = $debtService;
        $this->transactionService = $transactionService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai backfill transaksi untuk pembayaran...');
        $this->newLine();

        $result = $this->debtService->backfillPaymentTransactions();

        $this->info("Hasil backfill:");
        $this->table(
            ['Kategori', 'Jumlah'],
            [
                ['Total Pembayaran', $result['total']],
                ['Transaksi Dibuat', $result['created']],
                ['Transaksi Di-link', $result['linked']],
                ['Pembayaran Dilewati', $result['skipped']],
            ]
        );

        if ($result['created'] > 0 || $result['linked'] > 0) {
            $this->info('Membersihkan cache summary transaksi...');
            $this->transactionService->clearAllSummaryCache();
        }

        $this->newLine();
        $this->info('Backfill selesai!');

        return Command::SUCCESS;
    }
}

