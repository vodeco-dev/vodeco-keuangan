<?php

namespace Tests\Feature;

use App\Enums\InvoicePortalPassphraseAccessType;
use App\Models\InvoicePortalPassphrase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoicePortalPassphraseVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_passphrase_verification_stores_session_and_logs_usage(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-03-10 08:00:00'));

        $creator = User::factory()->create();
        $passphrase = new InvoicePortalPassphrase([
            'public_id' => (string) Str::uuid(),
            'access_type' => InvoicePortalPassphraseAccessType::CUSTOMER_SERVICE,
            'label' => 'Tim Customer Service',
            'is_active' => true,
            'created_by' => $creator->id,
        ]);
        $passphrase->setPassphrase('ValidPassphrase12');
        $passphrase->save();

        $response = $this->post(route('invoices.public.passphrase.verify'), [
            'passphrase' => 'ValidPassphrase12',
        ]);

        $response->assertRedirect(route('invoices.public.create'));
        $response->assertSessionHas('passphrase_verified');

        $sessionData = session('invoice_portal_passphrase');
        $this->assertSame($passphrase->id, $sessionData['id']);
        $this->assertSame($passphrase->access_type->value, $sessionData['access_type']);
        $this->assertSame($passphrase->displayLabel(), $sessionData['display_label']);

        $passphrase->refresh();
        $this->assertSame(1, $passphrase->usage_count);
        $this->assertNotNull($passphrase->last_used_at);

        $this->assertDatabaseHas('invoice_portal_passphrase_logs', [
            'invoice_portal_passphrase_id' => $passphrase->id,
            'action' => 'verified',
        ]);

        Carbon::setTestNow();
    }

    public function test_passphrase_verification_fails_for_invalid_passphrase(): void
    {
        $creator = User::factory()->create();
        $passphrase = new InvoicePortalPassphrase([
            'public_id' => (string) Str::uuid(),
            'access_type' => InvoicePortalPassphraseAccessType::CUSTOMER_SERVICE,
            'label' => 'Tim CS',
            'is_active' => true,
            'created_by' => $creator->id,
        ]);
        $passphrase->setPassphrase('ValidPassphrase12');
        $passphrase->save();

        $response = $this->from(route('invoices.public.create'))->post(route('invoices.public.passphrase.verify'), [
            'passphrase' => 'SalahPassphrase',
        ]);

        $response->assertRedirect(route('invoices.public.create'));
        $response->assertSessionHasErrors('passphrase', null, 'passphraseVerification');
        $this->assertNull(session('invoice_portal_passphrase'));
    }

    public function test_passphrase_logout_clears_session(): void
    {
        $this->withSession([
            'invoice_portal_passphrase' => [
                'id' => 10,
                'token' => 'encrypted-token',
            ],
        ]);

        $response = $this->post(route('invoices.public.passphrase.logout'));

        $response->assertRedirect(route('invoices.public.create'));
        $response->assertSessionHas('status', 'Sesi passphrase telah diakhiri.');
        $this->assertNull(session('invoice_portal_passphrase'));
    }
}
