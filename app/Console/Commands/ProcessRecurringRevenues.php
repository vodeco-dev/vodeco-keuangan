<?php

namespace App\Console\Commands;

use App\Models\RecurringRevenue;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessRecurringRevenues extends Command
{
    protected $signature = 'recurring:process';
    protected $description = 'Generate transactions and invoices for due recurring revenues';

    public function handle(): int
    {
        $due = RecurringRevenue::where('paused', false)->where('next_run', '<=', Carbon::now())->get();
        foreach ($due as $rev) {
            DB::transaction(function () use ($rev) {
                $transaction = Transaction::create([
                    'category_id' => $rev->category_id,
                    'user_id' => $rev->user_id,
                    'amount' => $rev->amount,
                    'description' => $rev->description ?? 'Recurring revenue',
                    'date' => Carbon::now(),
                ]);

                Invoice::create([
                    'recurring_revenue_id' => $rev->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $rev->amount,
                    'issued_at' => Carbon::now(),
                ]);

                $rev->update(['next_run' => $rev->next_run->add($rev->interval())]);
            });
        }

        return Command::SUCCESS;
    }
}
