<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Enums\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateDeveloperUserCommand extends Command
{
    protected $signature = 'user:create-developer 
                            {--email=developer@vodeco.co.id : Email untuk developer}
                            {--password= : Password untuk developer (default: password)}
                            {--name=Developer : Nama untuk developer}';

    protected $description = 'Membuat user admin untuk developer';

    public function handle(): int
    {
        $email = $this->option('email');
        $password = $this->option('password') ?: 'password';
        $name = $this->option('name');

        if (User::where('email', $email)->exists()) {
            $this->warn("User dengan email {$email} sudah ada.");
            
            if (!$this->confirm('Apakah Anda ingin mengupdate password user ini?', false)) {
                return Command::FAILURE;
            }

            $user = User::where('email', $email)->first();
            $user->update([
                'password' => Hash::make($password),
                'role' => Role::ADMIN,
                'email_verified_at' => now(),
            ]);

            $this->info("✓ User {$email} berhasil diupdate dengan password baru.");
            $this->line("  Email: {$email}");
            $this->line("  Password: {$password}");
            $this->line("  Role: Admin");
            
            return Command::SUCCESS;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => Role::ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->info("✓ User developer berhasil dibuat!");
        $this->line("  Email: {$email}");
        $this->line("  Password: {$password}");
        $this->line("  Role: Admin");

        return Command::SUCCESS;
    }
}

