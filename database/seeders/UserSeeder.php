<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\App;
use App\Enums\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        if (App::environment('production')) {
            // Jangan jalankan seeder ini di lingkungan produksi
            return;
        }

        // Cek apakah sudah ada user, jika sudah ada maka skip
        if (User::count() > 0) {
            return;
        }

        // Buat 3 user dengan role berbeda
        // 1. Admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@vodeco.co.id',
            'password' => Hash::make('password'),
            'role' => Role::ADMIN,
            'email_verified_at' => now(),
        ]);

        // 2. Accountant
        User::create([
            'name' => 'Accountant',
            'email' => 'accountant@vodeco.co.id',
            'password' => Hash::make('password'),
            'role' => Role::ACCOUNTANT,
            'email_verified_at' => now(),
        ]);

        // 3. Staff
        User::create([
            'name' => 'Staff',
            'email' => 'staff@vodeco.co.id',
            'password' => Hash::make('password'),
            'role' => Role::STAFF,
            'email_verified_at' => now(),
        ]);
    }
}
