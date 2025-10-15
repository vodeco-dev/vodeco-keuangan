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
        Schema::create('pass_through_packages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('customer_type')->index();
            $table->decimal('daily_balance', 15, 2)->default(0);
            $table->unsignedInteger('duration_days');
            $table->decimal('maintenance_fee', 15, 2)->default(0);
            $table->decimal('account_creation_fee', 15, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pass_through_packages');
    }
};
