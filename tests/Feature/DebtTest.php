<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_debt()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/debts', [
            'description' => 'Pinjam Uang',
            'related_party' => 'Budi',
            'type' => 'hutang',
            'amount' => 1000,
            'due_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertRedirect('/debts');

        $this->assertDatabaseHas('debts', [
            'description' => 'Pinjam Uang',
            'related_party' => 'Budi',
            'type' => 'hutang',
            'amount' => 1000,
            'status' => 'belum lunas',
        ]);
    }

    public function test_user_can_record_payment_and_mark_debt_paid()
    {
        $user = User::factory()->create();
        $debt = Debt::create([
            'description' => 'Test Debt',
            'related_party' => 'Budi',
            'type' => 'hutang',
            'amount' => 1000,
            'status' => 'belum lunas',
        ]);

        $response = $this->actingAs($user)->post(route('debts.payments.store', $debt), [
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
            'notes' => 'Lunas',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('payments', [
            'debt_id' => $debt->id,
            'amount' => 1000,
        ]);

        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'status' => 'lunas',
        ]);
    }
}
