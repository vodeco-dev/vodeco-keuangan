<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'user_id' => User::factory(),
            'amount' => $this->faker->numberBetween(100, 1000),
            'description' => $this->faker->sentence,
            'date' => now(),
        ];
    }
}
