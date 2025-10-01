<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class SendInvoiceReminder extends Command
{
    protected $signature = 'invoices:reminder';

    protected $description = 'Send reminders for due invoices';

    public function handle(): int
    {
        $invoices = Invoice::whereIn('status', ['belum bayar', 'belum lunas'])
            ->whereDate('due_date', '<=', now()->toDateString())
            ->get();

        foreach ($invoices as $invoice) {
            // Di sini bisa ditambahkan logika pengiriman email/notifikasi
            $this->info("Reminder sent for invoice {$invoice->number}");
        }

        return self::SUCCESS;
    }
}
