<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('customer_service_name')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('invoices', 'type')) {
                $table->string('type')->default('standard')->after('status');
            }

            if (! Schema::hasColumn('invoices', 'reference_invoice_id')) {
                $table->foreignId('reference_invoice_id')
                    ->nullable()
                    ->after('type')
                    ->constrained('invoices')
                    ->nullOnDelete();
            }
        });

        DB::table('invoices')->whereNull('type')->update(['type' => 'standard']);
        DB::table('invoices')->whereNull('created_by')->update(['created_by' => DB::raw('user_id')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'reference_invoice_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropConstrainedForeignId('reference_invoice_id');
            });
        }

        if (Schema::hasColumn('invoices', 'created_by')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }

        if (Schema::hasColumn('invoices', 'type')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
