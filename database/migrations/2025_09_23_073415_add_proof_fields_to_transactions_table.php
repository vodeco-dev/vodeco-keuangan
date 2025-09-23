<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('proof_disk', 50)->nullable()->after('date');
            $table->string('proof_directory')->nullable()->after('proof_disk');
            $table->string('proof_path')->nullable()->after('proof_directory');
            $table->string('proof_filename')->nullable()->after('proof_path');
            $table->string('proof_original_name')->nullable()->after('proof_filename');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'proof_disk',
                'proof_directory',
                'proof_path',
                'proof_filename',
                'proof_original_name',
            ]);
        });
    }
};
