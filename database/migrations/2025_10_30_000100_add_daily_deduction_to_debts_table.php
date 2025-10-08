<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            if (! Schema::hasColumn('debts', 'daily_deduction')) {
                $table->decimal('daily_deduction', 15, 2)
                    ->nullable()
                    ->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            if (Schema::hasColumn('debts', 'daily_deduction')) {
                $table->dropColumn('daily_deduction');
            }
        });
    }
};
