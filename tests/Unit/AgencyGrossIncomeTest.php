<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\ServiceCost;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyGrossIncomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_agency_gross_income_correctly(): void
    {
        $user = User::factory()->create();
        $incomeCategory = Category::factory()->create(['type' => 'pemasukan']);
        $expenseCategory = Category::factory()->create(['type' => 'pengeluaran']);

        $passThrough = ServiceCost::create(['name' => 'Pass-Through']);
        $downPayment = ServiceCost::create(['name' => 'Down Payment']);
        $agencyFee = ServiceCost::create(['name' => 'Agency Fee']);

        // Normal income
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'service_cost_id' => $agencyFee->id,
            'amount' => 1000,
            'date' => now(),
        ]);

        // Pass-through income
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'service_cost_id' => $passThrough->id,
            'amount' => 200,
            'date' => now(),
        ]);

        // Down payment income
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'service_cost_id' => $downPayment->id,
            'amount' => 300,
            'date' => now(),
        ]);

        // Expense transaction should not affect AGI
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $expenseCategory->id,
            'amount' => 400,
            'date' => now(),
        ]);

        $service = new TransactionService();
        $agi = $service->getAgencyGrossIncome($user);

        $this->assertEquals(1000, $agi);
    }
}

