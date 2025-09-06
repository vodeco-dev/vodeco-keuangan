<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCost;

class ServiceCostSeeder extends Seeder
{
    /**
     * Seed the service_costs table with default types.
     */
    public function run(): void
    {
        ServiceCost::updateOrCreate(
            ['id' => ServiceCost::PASS_THROUGH_ID],
            ['name' => 'Pass-Through']
        );

        ServiceCost::updateOrCreate(
            ['id' => ServiceCost::DOWN_PAYMENT_ID],
            ['name' => 'Down Payment']
        );

        ServiceCost::updateOrCreate(
            ['id' => 3],
            ['name' => 'Agency Fee']
        );
    }
}
