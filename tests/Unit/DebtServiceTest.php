<?php

namespace Tests\Unit;

use App\Models\Debt;
use App\Models\Payment;
use App\Models\User;
use App\Services\DebtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DebtServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_debts_applies_filters(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $matching = Debt::create([
            'user_id' => $user->id,
            'description' => 'Laptop purchase',
            'related_party' => 'Alice',
            'type' => Debt::TYPE_DOWN_PAYMENT,
            'amount' => 1000,
            'status' => Debt::STATUS_BELUM_LUNAS,
        ]);

        // Different status
        Debt::create([
            'user_id' => $user->id,
            'description' => 'Laptop purchase',
            'related_party' => 'Alice',
            'type' => Debt::TYPE_DOWN_PAYMENT,
            'amount' => 1000,
            'status' => Debt::STATUS_LUNAS,
        ]);

        // Different search
        Debt::create([
            'user_id' => $user->id,
            'description' => 'Phone purchase',
            'related_party' => 'Alice',
            'type' => Debt::TYPE_DOWN_PAYMENT,
            'amount' => 1000,
            'status' => Debt::STATUS_BELUM_LUNAS,
        ]);

        // Different user
        Debt::create([
            'user_id' => $otherUser->id,
            'description' => 'Laptop purchase',
            'related_party' => 'Alice',
            'type' => Debt::TYPE_DOWN_PAYMENT,
            'amount' => 1000,
            'status' => Debt::STATUS_BELUM_LUNAS,
        ]);

        $request = new Request([
            'status_filter' => Debt::STATUS_BELUM_LUNAS,
            'search' => 'Laptop',
        ]);

        $service = new DebtService();
        $debts = $service->getDebts($request, $user);

        $this->assertCount(1, $debts);
        $this->assertTrue($debts->first()->is($matching));
    }

    public function test_get_summary_returns_correct_totals(): void
    {
        $user = User::factory()->create();

        $downPaymentBelumLunas = Debt::create([
            'user_id' => $user->id,
            'description' => 'Project A',
            'related_party' => 'Alice',
            'type' => Debt::TYPE_DOWN_PAYMENT,
            'amount' => 200,
            'status' => Debt::STATUS_BELUM_LUNAS,
        ]);
        Payment::create([
            'debt_id' => $downPaymentBelumLunas->id,
            'amount' => 50,
            'payment_date' => now(),
        ]);

        $downPaymentBelumLunasDua = Debt::create([
            'user_id' => $user->id,
            'description' => 'Project B',
            'related_party' => 'Bob',
            'type' => Debt::TYPE_DOWN_PAYMENT,
            'amount' => 300,
            'status' => Debt::STATUS_BELUM_LUNAS,
        ]);

        $downPaymentLunas = Debt::create([
            'user_id' => $user->id,
            'description' => 'Project C',
            'related_party' => 'Carol',
            'type' => Debt::TYPE_DOWN_PAYMENT,
            'amount' => 100,
            'status' => Debt::STATUS_LUNAS,
        ]);
        Payment::create([
            'debt_id' => $downPaymentLunas->id,
            'amount' => 100,
            'payment_date' => now(),
        ]);

        $service = new DebtService();
        $summary = $service->getSummary($user);

        $this->assertEquals(500, $summary['totalDownPayment']);
        $this->assertEquals(450, $summary['totalBelumLunas']);
        $this->assertEquals(100, $summary['totalLunas']);
    }
}
