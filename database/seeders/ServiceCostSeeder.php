<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCost;
use Illuminate\Support\Str;

class ServiceCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $serviceCosts = [
            [
                'id' => ServiceCost::PASS_THROUGH_ID, 
                'name' => 'Pass-Through', 
                'slug' => ServiceCost::PASS_THROUGH_SLUG
            ],
            [
                'id' => ServiceCost::DOWN_PAYMENT_ID, 
                'name' => 'Down Payment', 
                'slug' => ServiceCost::DOWN_PAYMENT_SLUG
            ],
            [
                'id' => 3, 
                'name' => 'Agency Fee', 
                'slug' => 'agency-fee'
            ],
        ];

        // Use upsert to avoid duplicate entries when re-seeding
        ServiceCost::upsert($serviceCosts, ['id'], ['name', 'slug']);
    }
}
