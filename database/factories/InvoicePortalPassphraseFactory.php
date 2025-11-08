<?php

namespace Database\Factories;

use App\Enums\InvoicePortalPassphraseAccessType;
use App\Models\InvoicePortalPassphrase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class InvoicePortalPassphraseFactory extends Factory
{
    protected $model = InvoicePortalPassphrase::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
            'passphrase_hash' => Hash::make('test-passphrase'),
            'access_type' => InvoicePortalPassphraseAccessType::CUSTOMER_SERVICE,
            'label' => $this->faker->words(2, true),
            'is_active' => true,
            'expires_at' => now()->addDays(30),
            'last_used_at' => null,
            'usage_count' => 0,
            'created_by' => User::factory(),
            'deactivated_at' => null,
            'deactivated_by' => null,
        ];
    }
}

