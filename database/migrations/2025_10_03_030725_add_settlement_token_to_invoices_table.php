<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('settlement_token', 128)->nullable()->unique();
            $table->timestamp('settlement_token_expires_at')->nullable();
        });

        DB::table('invoices')
            ->orderBy('id')
            ->chunkById(100, function ($invoices): void {
                foreach ($invoices as $invoice) {
                    $expiresAt = now()->addDays(7);

                    DB::table('invoices')
                        ->where('id', $invoice->id)
                        ->update([
                            'settlement_token' => Str::random(64),
                            'settlement_token_expires_at' => $expiresAt,
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'settlement_token',
                'settlement_token_expires_at',
            ]);
        });
    }
};
