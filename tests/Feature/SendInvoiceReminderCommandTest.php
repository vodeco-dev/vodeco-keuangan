<?php

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendInvoiceReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_reminders_for_due_invoices(): void
    {
        $due = Invoice::create([
            'number' => 'INV-001',
            'issue_date' => now()->subWeek()->toDateString(),
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'Sent',
            'total' => 100,
            'client_name' => 'Client A',
            'client_email' => 'a@example.com',
            'client_address' => 'Address',
        ]);

        $future = Invoice::create([
            'number' => 'INV-002',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'Sent',
            'total' => 200,
            'client_name' => 'Client B',
            'client_email' => 'b@example.com',
            'client_address' => 'Address',
        ]);

        $draft = Invoice::create([
            'number' => 'INV-003',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'status' => 'Draft',
            'total' => 300,
            'client_name' => 'Client C',
            'client_email' => 'c@example.com',
            'client_address' => 'Address',
        ]);

        $this->artisan('invoices:reminder')
            ->expectsOutput("Reminder sent for invoice {$due->number}")
            ->doesntExpectOutput("Reminder sent for invoice {$future->number}")
            ->doesntExpectOutput("Reminder sent for invoice {$draft->number}")
            ->assertExitCode(0);
    }
}
