<?php

namespace Tests\Feature;

use App\Enums\InvoicePortalPassphraseAccessType;
use App\Enums\Role;
use App\Models\InvoicePortalPassphrase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoicePortalPassphraseTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_passphrase_index(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $creator = User::factory()->create();

        $passphrase = new InvoicePortalPassphrase([
            'public_id' => (string) Str::uuid(),
            'access_type' => InvoicePortalPassphraseAccessType::CUSTOMER_SERVICE,
            'label' => 'Tim A',
            'is_active' => true,
            'created_by' => $creator->id,
        ]);
        $passphrase->setPassphrase('PassphraseRahasia12');
        $passphrase->save();

        $response = $this->actingAs($admin)->get(route('invoice-portal.passphrases.index'));

        $response->assertOk();
        $response->assertViewIs('invoice-portal.passphrases.index');
        $response->assertViewHas('passphrases', function ($collection) use ($passphrase) {
            return $collection->contains(fn ($item) => $item->id === $passphrase->id);
        });
        $response->assertViewHas('accessTypes', InvoicePortalPassphraseAccessType::cases());
    }

    public function test_admin_can_store_passphrase_with_random_generation(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-01 00:00:00'));

        $admin = User::factory()->create(['role' => Role::ADMIN]);

        $response = $this->actingAs($admin)->post(route('invoice-portal.passphrases.store'), [
            'access_type' => InvoicePortalPassphraseAccessType::CUSTOMER_SERVICE->value,
            'label' => 'Tim Sales',
            'expires_at' => Carbon::now()->addDays(7)->toDateTimeString(),
            'passphrase' => '',
        ]);

        $response->assertRedirect(route('invoice-portal.passphrases.index'));
        $response->assertSessionHas('status', 'Passphrase portal invoice baru berhasil dibuat.');
        $response->assertSessionHas('passphrase_plain', function (array $data) {
            return strlen($data['value']) === 16 && $data['label'] === 'Tim Sales (Customer Service)';
        });

        $passphrase = InvoicePortalPassphrase::where('label', 'Tim Sales')->first();
        $this->assertNotNull($passphrase);
        $this->assertTrue($passphrase->is_active);
        $this->assertDatabaseHas('invoice_portal_passphrases', [
            'id' => $passphrase->id,
            'access_type' => InvoicePortalPassphraseAccessType::CUSTOMER_SERVICE->value,
        ]);
        $this->assertDatabaseHas('invoice_portal_passphrase_logs', [
            'invoice_portal_passphrase_id' => $passphrase->id,
            'action' => 'generated',
        ]);

        Carbon::setTestNow();
    }

    public function test_admin_can_rotate_active_passphrase(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-02-01 00:00:00'));

        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $creator = User::factory()->create();

        $passphrase = new InvoicePortalPassphrase([
            'public_id' => (string) Str::uuid(),
            'access_type' => InvoicePortalPassphraseAccessType::CUSTOMER_SERVICE,
            'label' => 'Tim Lama',
            'is_active' => true,
            'created_by' => $creator->id,
        ]);
        $passphrase->setPassphrase('PassphraseLama123');
        $passphrase->save();

        $response = $this->actingAs($admin)->post(route('invoice-portal.passphrases.rotate', $passphrase), [
            'label' => 'Tim Baru',
            'passphrase' => 'PassphraseBaru12',
            'expires_at' => Carbon::now()->addDays(10)->toDateTimeString(),
        ]);

        $response->assertRedirect(route('invoice-portal.passphrases.index'));
        $response->assertSessionHas('status', 'Passphrase berhasil diperbarui. Pastikan segera dibagikan ke pihak terkait.');
        $response->assertSessionHas('passphrase_plain', [
            'value' => 'PassphraseBaru12',
            'label' => 'Tim Baru (Customer Service)',
        ]);

        $updated = $passphrase->fresh();
        $this->assertSame('Tim Baru', $updated->label);
        $this->assertTrue($updated->is_active);
        $this->assertTrue($updated->expires_at->greaterThan(Carbon::now()));

        $this->assertDatabaseHas('invoice_portal_passphrase_logs', [
            'invoice_portal_passphrase_id' => $passphrase->id,
            'action' => 'rotated',
        ]);

        Carbon::setTestNow();
    }

    public function test_admin_can_deactivate_passphrase(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $creator = User::factory()->create();

        $passphrase = new InvoicePortalPassphrase([
            'public_id' => (string) Str::uuid(),
            'access_type' => InvoicePortalPassphraseAccessType::ADMIN_PELUNASAN,
            'label' => 'Tim Pelunasan',
            'is_active' => true,
            'created_by' => $creator->id,
        ]);
        $passphrase->setPassphrase('RahasiaPelunasan12');
        $passphrase->save();

        $response = $this->actingAs($admin)->delete(route('invoice-portal.passphrases.deactivate', $passphrase));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Passphrase berhasil dinonaktifkan.');

        $passphrase->refresh();
        $this->assertFalse($passphrase->is_active);
        $this->assertNotNull($passphrase->deactivated_at);
        $this->assertSame($admin->id, $passphrase->deactivated_by);
        $this->assertDatabaseHas('invoice_portal_passphrases', [
            'id' => $passphrase->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('invoice_portal_passphrase_logs', [
            'invoice_portal_passphrase_id' => $passphrase->id,
            'action' => 'deactivated',
        ]);
    }

    public function test_deactivate_returns_message_when_passphrase_already_inactive(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $creator = User::factory()->create();

        $passphrase = new InvoicePortalPassphrase([
            'public_id' => (string) Str::uuid(),
            'access_type' => InvoicePortalPassphraseAccessType::CUSTOMER_SERVICE,
            'label' => 'Tim Nonaktif',
            'is_active' => false,
            'created_by' => $creator->id,
        ]);
        $passphrase->setPassphrase('PassphraseTidakAktif12');
        $passphrase->save();

        $response = $this->actingAs($admin)->delete(route('invoice-portal.passphrases.deactivate', $passphrase));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Passphrase sudah tidak aktif.');
    }
}
