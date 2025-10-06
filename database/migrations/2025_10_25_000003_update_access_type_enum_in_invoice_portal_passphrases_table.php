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
        Schema::table('invoice_portal_passphrases', function (Blueprint $table) {
            $table->enum('access_type', ['customer_service', 'admin_pelunasan', 'admin_perpanjangan'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('invoice_portal_passphrases')
            ->where('access_type', 'admin_perpanjangan')
            ->update(['access_type' => 'customer_service']);

        Schema::table('invoice_portal_passphrases', function (Blueprint $table) {
            $table->enum('access_type', ['customer_service', 'admin_pelunasan'])->change();
        });
    }
};
