<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendInvoiceReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_reminders_for_due_invoices(): void
    {
        $user = User::factory()->create();

        $due = Invoice::factory()->create([
            'user_id' => $user->id,
            'due_date' => now()->subDay(),
            'status' => 'belum bayar',
        ]);

        $future = Invoice::factory()->create([
            'user_id' => $user->id,
            'due_date' => now()->addDay(),
            'status' => 'belum bayar',
        ]);

        $paid = Invoice::factory()->create([
            'user_id' => $user->id,
            'due_date' => now(),
            'status' => 'lunas',
        ]);

        $this->artisan('invoices:reminder')
            ->expectsOutput("Reminder sent for invoice {$due->number}")
            ->doesntExpectOutput("Reminder sent for invoice {$future->number}")
            ->doesntExpectOutput("Reminder sent for invoice {$paid->number}")
            ->assertExitCode(0);
    }
}
