<?php

namespace Tests\Feature;

use App\Enums\InvoicePortalPassphraseAccessType;
use App\Enums\Role;
use App\Models\AccessCode;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\CustomerService;
use App\Models\Debt;
use App\Models\Invoice;
use App\Models\InvoicePortalPassphrase;
use App\Models\InvoicePortalPassphraseLog;
use App\Models\Transaction;
use App\Models\TransactionDeletionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettingsDataManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_purge_data(): void
    {
        $staff = User::factory()->create(['role' => Role::STAFF]);

        $response = $this->actingAs($staff)->delete(route('settings.data.purge'));

        $response->assertForbidden();
    }

    public function test_admin_can_purge_data_and_preserve_admin_user(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => Role::ADMIN,
            'name' => 'Admin',
        ]);

        $staff = User::factory()->create([
            'role' => Role::STAFF,
            'name' => 'Staff',
        ]);

        $category = Category::factory()->create();

        $transaction = Transaction::factory()->create([
            'category_id' => $category->id,
            'user_id' => $staff->id,
            'proof_disk' => 'local',
            'proof_directory' => 'transaction-proofs',
            'proof_path' => 'bukti.jpg',
        ]);

        Storage::disk('local')->put('transaction-proofs/bukti.jpg', 'dummy');

        TransactionDeletionRequest::create([
            'transaction_id' => $transaction->id,
            'requested_by' => $staff->id,
            'status' => 'pending',
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $staff->id,
            'created_by' => $admin->id,
            'number' => 'INV-TEST-001',
        ]);

        $debt = Debt::factory()->create([
            'user_id' => $staff->id,
            'category_id' => $category->id,
            'invoice_id' => $invoice->id,
        ]);

        $debt->payments()->create([
            'amount' => 50000,
            'payment_date' => now()->toDateString(),
            'notes' => 'Test payment',
        ]);

        $invoice->items()->create([
            'description' => 'Item',
            'quantity' => 1,
            'price' => 100000,
            'category_id' => $category->id,
        ]);

        CustomerService::create([
            'name' => 'CS',
            'email' => 'cs@example.com',
            'phone' => '0800000000',
            'user_id' => $admin->id,
        ]);

        AccessCode::create([
            'public_id' => (string) Str::uuid(),
            'user_id' => $staff->id,
            'role' => Role::STAFF,
            'code_hash' => Hash::make('secret'),
        ]);

        $passphrase = InvoicePortalPassphrase::create([
            'public_id' => (string) Str::uuid(),
            'passphrase_hash' => Hash::make('super-secret'),
            'access_type' => InvoicePortalPassphraseAccessType::CUSTOMER_SERVICE,
            'created_by' => $admin->id,
        ]);

        InvoicePortalPassphraseLog::create([
            'invoice_portal_passphrase_id' => $passphrase->id,
            'action' => 'created',
        ]);

        ActivityLog::create([
            'user_id' => $staff->id,
            'description' => 'Testing',
            'method' => 'POST',
            'url' => '/transactions',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($admin)->delete(route('settings.data.purge'));

        $response->assertRedirect(route('settings.data'));
        $response->assertSessionHas('success', 'Seluruh data berhasil dihapus, kecuali akun Admin.');

        Storage::disk('local')->assertMissing('transaction-proofs/bukti.jpg');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_deletion_requests', 0);
        $this->assertDatabaseCount('debts', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertDatabaseCount('customer_services', 0);
        $this->assertDatabaseCount('invoice_portal_passphrases', 0);
        $this->assertDatabaseCount('invoice_portal_passphrase_logs', 0);
        $this->assertDatabaseCount('access_codes', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'description' => 'Menghapus settings/data/purge',
        ]);

        $this->assertDatabaseHas('users', ['name' => 'Admin']);
        $this->assertDatabaseMissing('users', ['name' => 'Staff']);
        $this->assertSame(1, User::count());
    }
}
