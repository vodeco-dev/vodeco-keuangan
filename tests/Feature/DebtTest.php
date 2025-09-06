<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public function test_payment_failure_is_logged_and_returns_error()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $debt = Debt::create([
            'user_id' => $user->id,
            'description' => 'Test Debt',
            'related_party' => 'Budi',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'status' => 'belum lunas',
            'due_date' => now()->addWeek(),
        ]);

        $originalDb = DB::getFacadeRoot();
        DB::shouldReceive('transaction')->once()->andThrow(new \Exception('db error'));
        Log::shouldReceive('error')->once()->with('db error');

        $response = $this->post(route('debts.pay', $debt), [
            'payment_amount' => 1000,
        ]);

        DB::swap($originalDb);

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('debts', [
            'id' => $debt->id,
            'status' => 'belum lunas',
        ]);
    }
}
