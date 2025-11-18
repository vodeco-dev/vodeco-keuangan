<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Enums\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateDeveloperUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-developer 
                            {--email=developer@vodeco.co.id : Email untuk developer}
                            {--password= : Password untuk developer (default: password)}
                            {--name=Developer : Nama untuk developer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membuat user admin untuk developer';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email');
        $password = $this->option('password') ?: 'password';
        $name = $this->option('name');

        // Cek apakah user sudah ada
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

        // Buat user baru
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

