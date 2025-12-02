<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\App;
use App\Enums\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {

        if (App::environment('production')) {
            return;
        }

        if (User::count() > 0) {
            return;
        }

        User::create([
            'name' => 'Admin',
            'email' => 'admin@vodeco.co.id',
            'password' => Hash::make('password'),
            'role' => Role::ADMIN,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Accountant',
            'email' => 'accountant@vodeco.co.id',
            'password' => Hash::make('password'),
            'role' => Role::ACCOUNTANT,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Staff',
            'email' => 'staff@vodeco.co.id',
            'password' => Hash::make('password'),
            'role' => Role::STAFF,
            'email_verified_at' => now(),
        ]);
    }
}
