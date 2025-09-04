<?php

namespace App\Console\Commands;

use App\Models\RecurringRevenue;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
                    'number' => (string) Str::uuid(),
                    'issue_date' => Carbon::now()->toDateString(),
                    'due_date' => Carbon::now()->toDateString(),
                    'status' => 'Sent',
                    'total' => $rev->amount,
                    'client_name' => $rev->user->name ?? 'Client',
                    'client_email' => $rev->user->email ?? 'client@example.com',
                    'client_address' => '-',
                ]);

                $rev->update(['next_run' => $rev->next_run->add($rev->interval())]);
            });
        }

        return Command::SUCCESS;
    }
}
