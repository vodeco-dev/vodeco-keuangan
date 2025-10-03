<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Enums\Role;
use App\Models\AccessCode;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() :void{
        // hapus user yang mungkin sudah ada dengan email yang sama
        User::whereIn('email', [
            'admin@vodeco.co.id',
            'staff@vodeco.co.id',
            'accountant@vodeco.co.id',
            'cs@vodeco.co.id',
            'pelunasan@vodeco.co.id',
        ])->delete();

        // Buat User Admin baru
        User::create([
            'name' => 'Admin',
            'email' => 'admin@vodeco.co.id',
            'password' => Hash::make('masukaja'),
            'role' => Role::ADMIN,
        ]);

        User::create([
            'name' => 'Staff',
            'email' => 'staff@vodeco.co.id',
            'password' => Hash::make('masukaja'),
            'role' => Role::STAFF,
        ]);

        User::create([
            'name' => 'Accountant',
            'email' => 'accountant@vodeco.co.id',
            'password' => Hash::make('masukaja'),
            'role' => Role::ACCOUNTANT,
        ]);

        $customerService = User::create([
            'name' => 'Customer Service',
            'email' => 'cs@vodeco.co.id',
            'password' => Hash::make('masukaja'),
            'role' => Role::CUSTOMER_SERVICE,
        ]);

        $settlementAdmin = User::create([
            'name' => 'Admin Pelunasan',
            'email' => 'pelunasan@vodeco.co.id',
            'password' => Hash::make('masukaja'),
            'role' => Role::SETTLEMENT_ADMIN,
        ]);

        $this->seedAccessCode($customerService, Role::CUSTOMER_SERVICE, '11111111-1111-4111-8111-111111111111', 'CS-ACCESS-001');
        $this->seedAccessCode($settlementAdmin, Role::SETTLEMENT_ADMIN, '22222222-2222-4222-8222-222222222222', 'PELUNASAN-001');
    }

    private function seedAccessCode(User $user, Role $role, string $publicId, string $rawCode): void
    {
        AccessCode::where('public_id', $publicId)->delete();

        AccessCode::create([
            'public_id' => $publicId,
            'user_id' => $user->id,
            'role' => $role,
            'code_hash' => Hash::make($rawCode),
            'used_at' => null,
            'used_by' => null,
            'expires_at' => now()->addMonths(3),
        ]);
    }
}
