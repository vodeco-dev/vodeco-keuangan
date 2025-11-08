<?php

namespace Database\Factories;

use App\Models\PassThroughPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

class PassThroughPackageFactory extends Factory
{
    protected $model = PassThroughPackage::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => $this->faker->words(3, true),
            'customer_type' => $this->faker->randomElement(['new', 'existing']),
            'daily_balance' => $this->faker->randomFloat(2, 100000, 10000000),
            'duration_days' => $this->faker->numberBetween(7, 365),
            'maintenance_fee' => $this->faker->randomFloat(2, 0, 100000),
            'account_creation_fee' => $this->faker->randomFloat(2, 0, 500000),
            'is_active' => true,
        ];
    }
}

