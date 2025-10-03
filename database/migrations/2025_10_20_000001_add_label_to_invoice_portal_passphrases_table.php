<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_portal_passphrases', function (Blueprint $table) {
            $table->string('label')->nullable()->after('access_type');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_portal_passphrases', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
