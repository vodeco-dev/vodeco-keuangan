<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'number' => $this->faker->unique()->numerify('INV-2025-#####'),
            'client_name' => $this->faker->name,
            'client_whatsapp' => $this->faker->unique()->numerify('08##########'),
            'client_address' => $this->faker->address,
            'issue_date' => now(),
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'status' => 'belum bayar',
            'total' => $this->faker->randomFloat(2, 100, 1000),
            'settlement_token' => Str::random(64),
            'settlement_token_expires_at' => now()->addDays(7),
        ];
    }
}
