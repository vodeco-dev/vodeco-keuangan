<?php

namespace Database\Factories;

use App\Models\ServiceCost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServiceCostFactory extends Factory
{
    protected $model = ServiceCost::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
