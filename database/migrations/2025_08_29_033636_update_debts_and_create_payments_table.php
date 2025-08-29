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
        // 1. Menambahkan kolom baru ke tabel 'debts' yang sudah ada
        Schema::table('debts', function (Blueprint $table) {
            $table->string('related_party')->after('description'); // Kolom untuk nama pihak terkait
        });

        // 2. Membuat tabel baru untuk mencatat cicilan/pembayaran
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained()->onDelete('cascade'); // Relasi ke tabel debts
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');

        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn('related_party');
        });
    }
};