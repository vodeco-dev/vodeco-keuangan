<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement(
                'UPDATE invoices SET user_id = (
                    SELECT user_id FROM recurring_revenues WHERE invoices.recurring_revenue_id = recurring_revenues.id
                ) WHERE recurring_revenue_id IS NOT NULL'
            );
        } else {
            DB::table('invoices')
                ->join('recurring_revenues', 'invoices.recurring_revenue_id', '=', 'recurring_revenues.id')
                ->update(['invoices.user_id' => DB::raw('recurring_revenues.user_id')]);

            DB::statement('ALTER TABLE invoices MODIFY user_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
