<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN role ENUM('admin','accountant','staff','customer_service','settlement_admin')
            DEFAULT 'staff'
        ");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN role ENUM('admin','accountant','staff')
            DEFAULT 'staff'
        ");
    }
};
