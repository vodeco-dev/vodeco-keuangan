<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->string('description')->nullable();
            $table->date('date')->nullable();
            $table->string('proof_disk', 50)->nullable();
            $table->string('proof_directory')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('proof_filename')->nullable();
            $table->string('proof_original_name')->nullable();
            $table->string('proof_remote_id')->nullable();
            $table->string('proof_token', 64)->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
