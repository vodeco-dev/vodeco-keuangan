<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DebtFactory extends Factory
{
    protected $model = Debt::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'description' => $this->faker->sentence,
            'related_party' => $this->faker->name,
            'type' => $this->faker->randomElement([Debt::TYPE_PASS_THROUGH, Debt::TYPE_DOWN_PAYMENT]),
            'amount' => $this->faker->numberBetween(1000, 100000),
            'due_date' => $this->faker->date(),
            'status' => $this->faker->randomElement([
                Debt::STATUS_BELUM_LUNAS,
                Debt::STATUS_LUNAS,
                Debt::STATUS_GAGAL,
            ]),
        ];
    }
}
