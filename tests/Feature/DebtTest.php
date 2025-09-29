<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Debt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function PHPSTORM_META\type;

class DebtTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_pass_through_debt()
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Operasional',
            'type' => 'pengeluaran',
        ]);

        $response = $this->actingAs($user)->post('/debts', [
            'description' => 'Pass Through Expense',
            'related_party' => 'Budi',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'due_date' => now()->addMonth()->toDateString(),
            'category_id' => $category->id,
        ]);

        $response->assertRedirect('/debts');

        $this->assertDatabaseHas('debts', [
            'description' => 'Pass Through Expense',
            'related_party' => 'Budi',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'status' => Debt::STATUS_BELUM_LUNAS,
            'category_id' => $category->id,
        ]);
    }

    public function test_user_can_record_payment_and_mark_down_payment_paid()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::create([
            'name' => 'Pembayaran Hutang',
            'type' => 'pengeluaran',
        ]);

        // FIX: Kembali menggunakan create() dan pastikan user_id ada.
        $debt = Debt::create([
            'user_id' => $user->id,
            'description' => 'Test Debt',
            'related_party' => 'Budi',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'status' => Debt::STATUS_BELUM_LUNAS,
            'due_date' => now()->addWeek(),
            'category_id' => $category->id,
        ]);

        // ... sisa kode tes tidak perlu diubah
        $response = $this->post(route('debts.pay', $debt), [
            'payment_amount' => 1000,
            'category_id' => $category->id,
        ]);

        $response->assertRedirect(route('debts.index'));
        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'status' => Debt::STATUS_LUNAS,
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
