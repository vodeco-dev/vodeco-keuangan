<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_items', 'category_id')) {
                $table->foreignId('category_id')
                    ->nullable()
                    ->after('invoice_id')
                    ->constrained('categories')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'category_id')) {
                $table->dropConstrainedForeignId('category_id');
            }
        });
    }
};
