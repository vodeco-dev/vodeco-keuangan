<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Enums\Role;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates test users for API testing
     */
    public function run(): void
    {
        // Test users for API testing
        $testUsers = [
            [
                'name' => 'Test Admin',
                'email' => 'admin@example.com',
                'password' => 'AdminPass123!',
                'role' => Role::ADMIN,
            ],
            [
                'name' => 'Test Accountant',
                'email' => 'accountant@example.com',
                'password' => 'AccPass123!',
                'role' => Role::ACCOUNTANT,
            ],
            [
                'name' => 'Test Staff',
                'email' => 'staff@example.com',
                'password' => 'StaffPass123!',
                'role' => Role::STAFF,
            ],
            [
                'name' => 'Test User',
                'email' => 'testuser@example.com',
                'password' => 'Password123',
                'role' => Role::STAFF, // Using STAFF as default user role
            ],
            [
                'name' => 'Test User Role Management',
                'email' => 'testuser_role_management@example.com',
                'password' => 'TestUserPass123!',
                'role' => Role::STAFF, // Using STAFF as default user role
            ],
        ];

        foreach ($testUsers as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'role' => $userData['role'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}

