<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Temporarily change the column to string to allow updating values
        Schema::table('debts', function (Blueprint $table) {
            $table->string('type', 20)->change();
        });

        // Migrate existing data to new enum values
        DB::table('debts')->where('type', 'hutang')->update(['type' => 'pass_through']);
        DB::table('debts')->where('type', 'piutang')->update(['type' => 'down_payment']);

        // Change the column back to enum with new values
        Schema::table('debts', function (Blueprint $table) {
            $table->enum('type', ['pass_through', 'down_payment'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert column to string to allow reverting values
        Schema::table('debts', function (Blueprint $table) {
            $table->string('type', 20)->change();
        });

        // Revert data to old enum values
        DB::table('debts')->where('type', 'pass_through')->update(['type' => 'hutang']);
        DB::table('debts')->where('type', 'down_payment')->update(['type' => 'piutang']);

        // Restore original enum definition
        Schema::table('debts', function (Blueprint $table) {
            $table->enum('type', ['hutang', 'piutang'])->change();
        });
    }
};
