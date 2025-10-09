<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_proof_disk')->nullable()->after('settlement_token_expires_at');
            $table->string('payment_proof_path')->nullable()->after('payment_proof_disk');
            $table->string('payment_proof_filename')->nullable()->after('payment_proof_path');
            $table->string('payment_proof_original_name')->nullable()->after('payment_proof_filename');
            $table->timestamp('payment_proof_uploaded_at')->nullable()->after('payment_proof_original_name');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'payment_proof_disk',
                'payment_proof_path',
                'payment_proof_filename',
                'payment_proof_original_name',
                'payment_proof_uploaded_at',
            ]);
        });
    }
};
