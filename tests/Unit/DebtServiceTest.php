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
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'status' => 'belum lunas',
        ]);

        // Different status
        Debt::create([
            'user_id' => $user->id,
            'description' => 'Laptop purchase',
            'related_party' => 'Alice',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'status' => 'lunas',
        ]);

        // Different type
        Debt::create([
            'user_id' => $user->id,
            'description' => 'Laptop purchase',
            'related_party' => 'Alice',
            'type' => Debt::TYPE_DOWN_PAYMENT,
            'amount' => 1000,
            'status' => 'belum lunas',
        ]);

        // Different search
        Debt::create([
            'user_id' => $user->id,
            'description' => 'Phone purchase',
            'related_party' => 'Alice',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'status' => 'belum lunas',
        ]);

        // Different user
        Debt::create([
            'user_id' => $otherUser->id,
            'description' => 'Laptop purchase',
            'related_party' => 'Alice',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 1000,
            'status' => 'belum lunas',
        ]);

        $request = new Request([
            'type_filter' => Debt::TYPE_PASS_THROUGH,
            'status_filter' => 'belum lunas',
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

        $ptBelumLunas = Debt::create([
            'user_id' => $user->id,
            'description' => 'Project A',
            'related_party' => 'Alice',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 200,
            'status' => 'belum lunas',
        ]);
        Payment::create([
            'debt_id' => $ptBelumLunas->id,
            'amount' => 50,
            'payment_date' => now(),
        ]);

        $dpBelumLunas = Debt::create([
            'user_id' => $user->id,
            'description' => 'Project B',
            'related_party' => 'Bob',
            'type' => Debt::TYPE_DOWN_PAYMENT,
            'amount' => 300,
            'status' => 'belum lunas',
        ]);

        $ptLunas = Debt::create([
            'user_id' => $user->id,
            'description' => 'Project C',
            'related_party' => 'Carol',
            'type' => Debt::TYPE_PASS_THROUGH,
            'amount' => 100,
            'status' => 'lunas',
        ]);
        Payment::create([
            'debt_id' => $ptLunas->id,
            'amount' => 100,
            'payment_date' => now(),
        ]);

        $service = new DebtService();
        $summary = $service->getSummary($user);

        $this->assertEquals(300, $summary['totalPassThrough']);
        $this->assertEquals(300, $summary['totalDownPayment']);
        $this->assertEquals(450, $summary['totalBelumLunas']);
        $this->assertEquals(100, $summary['totalLunas']);
    }
}
