<?php

use App\Enums\Role;
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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('customer_service_id')
                ->nullable()
                ->after('user_id')
                ->constrained('customer_services')
                ->nullOnDelete();
            $table->string('customer_service_name')->nullable()->after('customer_service_id');
        });

        DB::transaction(function () {
            $users = DB::table('users')
                ->whereIn('role', [
                    Role::ADMIN->value,
                    Role::ACCOUNTANT->value,
                    Role::STAFF->value,
                    Role::CUSTOMER_SERVICE->value,
                    Role::SETTLEMENT_ADMIN->value,
                ])
                ->get();

            $existingCustomerServices = DB::table('customer_services')
                ->pluck('id', 'user_id');

            foreach ($users as $user) {
                if ($existingCustomerServices->has($user->id)) {
                    continue;
                }

                $customerServiceId = DB::table('customer_services')->insertGetId([
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => null,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $existingCustomerServices->put($user->id, $customerServiceId);
            }

            $customerServices = DB::table('customer_services')
                ->select('id', 'name', 'user_id')
                ->get()
                ->keyBy('user_id');

            $invoices = DB::table('invoices')->get();

            foreach ($invoices as $invoice) {
                $customerService = $customerServices->get($invoice->user_id);

                DB::table('invoices')
                    ->where('id', $invoice->id)
                    ->update([
                        'customer_service_id' => $customerService->id ?? null,
                        'customer_service_name' => $customerService->name ?? null,
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('invoices')->update([
            'customer_service_id' => null,
            'customer_service_name' => null,
        ]);

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_service_id');
            $table->dropColumn('customer_service_name');
        });
    }
};
