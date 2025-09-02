<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Memanggil seeder untuk membuat user awal (Admin, Staff, dll.)
        $this->call([
            UserSeeder::class,
            // Anda bisa menambahkan seeder lain di sini nanti, contoh:
            // CategorySeeder::class,
            // TransactionSeeder::class,
        ]);
    }
}
