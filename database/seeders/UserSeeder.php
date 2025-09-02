<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() :void{
        // hapus user yang mungkin sudah ada dengan email yang sama
        User::where('email', 'admin@vodeco.co.id')->delete();
        User::where('email', 'staff@vodeco.co.id')->delete();
        User::where('email', 'accountant@vodeco.co.id')->delete();

        // Buat User Admin baru
        User::create([
            'name' => 'Admin',
            'email' => 'admin@vodeco.co.id',
            'password' => Hash::make('masukaja'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Staff',
            'email' => 'staff@vodeco.co.id',
            'password' => Hash::make('masukaja'),
            'role' => 'staff',
        ]);

        User::create([
            'name' => 'Accountant',
            'email' => 'accountant@vodeco.co.id',
            'password' => Hash::make('masukaja'),
            'role' => 'accountant',
        ]);
    }
}