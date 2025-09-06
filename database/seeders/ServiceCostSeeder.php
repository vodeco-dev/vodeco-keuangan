<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCost;

class ServiceCostSeeder extends Seeder
{
    public function run(): void
    {
        $serviceCosts = [
            ['id' => ServiceCost::PASS_THROUGH_ID, 'name' => 'Pass-Through'],
            ['id' => ServiceCost::DOWN_PAYMENT_ID, 'name' => 'Down Payment'],
            ['id' => 3, 'name' => 'Agency Fee'],
        ];

        ServiceCost::upsert($serviceCosts, ['id'], ['name']);
    }
}
