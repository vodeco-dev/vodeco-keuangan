<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'debt_id' => Debt::factory(),
            'amount' => $this->faker->randomFloat(2, 1000, 100000),
            'payment_date' => now(),
            'notes' => $this->faker->sentence,
        ];
    }
}

