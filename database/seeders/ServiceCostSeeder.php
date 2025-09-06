<?php

namespace Database\Seeders;

use App\Models\ServiceCost;
use Illuminate\Database\Seeder;

class ServiceCostSeeder extends Seeder
{
    public function run(): void
    {
        ServiceCost::updateOrCreate(
            ['slug' => ServiceCost::PASS_THROUGH_SLUG],
            ['name' => 'Pass-Through']
        );

        ServiceCost::updateOrCreate(
            ['slug' => ServiceCost::DOWN_PAYMENT_SLUG],
            ['name' => 'Down Payment']
        );
    }
}
