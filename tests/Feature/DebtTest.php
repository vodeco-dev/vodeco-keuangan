<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function PHPSTORM_META\type;

class DebtTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_pass_through_debt()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/debts', [
            'description' => 'Pass Through Expense',
            'related_party' => 'Budi',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'due_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertRedirect('/debts');

        $this->assertDatabaseHas('debts', [
            'description' => 'Pass Through Expense',
            'related_party' => 'Budi',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'status' => 'belum lunas',
        ]);
    }

    public function test_user_can_record_payment_and_mark_down_payment_paid()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // FIX: Kembali menggunakan create() dan pastikan user_id ada.
        $debt = Debt::create([
            'user_id' => $user->id,
            'description' => 'Test Debt',
            'related_party' => 'Budi',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'status' => 'belum lunas',
            'due_date' => now()->addWeek(),
        ]);

        // ... sisa kode tes tidak perlu diubah
        $response = $this->post(route('debts.pay', $debt), [
            'payment_amount' => 1000,
        ]);

        $response->assertRedirect(route('debts.index'));
        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'status' => 'lunas',
        ]);
    }

    public function test_debts_index_is_paginated()
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 20; $i++) {
            Debt::create([
                'user_id' => $user->id,
                'description' => 'Debt ' . $i,
                'related_party' => 'Budi',
                'type' => Debt::TYPE_PASS_THROUGH,
                'amount' => 1000,
                'status' => 'belum lunas',
            ]);
        }

        $response = $this->actingAs($user)->get('/debts');

        $response->assertViewHas('debts', function ($debts) {
            return $debts->perPage() == 15 && $debts->total() == 20;
        });

        $responsePage2 = $this->actingAs($user)->get('/debts?page=2');
        $responsePage2->assertViewHas('debts', function ($debts) {
            return $debts->currentPage() == 2 && $debts->count() == 5;
        });
    }
}
