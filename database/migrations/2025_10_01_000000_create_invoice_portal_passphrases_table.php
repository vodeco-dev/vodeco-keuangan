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
        Schema::create('invoice_portal_passphrases', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('passphrase_hash');
            $table->enum('access_type', ['customer_service', 'admin_pelunasan']);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_portal_passphrase_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_portal_passphrase_id');
            $table->foreign('invoice_portal_passphrase_id', 'passphrase_log_passphrase_fk')
                ->references('id')
                ->on('invoice_portal_passphrases')
                ->cascadeOnDelete();
            $table->string('action');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_portal_passphrase_logs');
        Schema::dropIfExists('invoice_portal_passphrases');
    }
};
