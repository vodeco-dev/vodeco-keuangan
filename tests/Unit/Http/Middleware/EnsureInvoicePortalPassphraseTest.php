<?php

namespace Tests\Unit\Http\Middleware;

use App\Models\InvoicePortalPassphrase;
use App\Http\Middleware\EnsureInvoicePortalPassphrase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class EnsureInvoicePortalPassphraseTest extends TestCase
{
    use RefreshDatabase;

    private EnsureInvoicePortalPassphrase $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureInvoicePortalPassphrase();
    }

    public function test_allows_request_when_passphrase_is_valid_in_session(): void
    {
        $passphrase = InvoicePortalPassphrase::factory()->create([
            'is_active' => true,
            'expires_at' => now()->addDay(),
        ]);

        $request = $this->createRequestWithSession([
            'invoice_portal_passphrase' => [
                'id' => $passphrase->id,
                'verified_at' => now()->toIso8601String(),
            ],
        ]);

        $response = $this->middleware->handle(
            $request,
            fn () => new Response('ok'),
            'optional'
        );

        $this->assertSame('ok', $response->getContent());
        $this->assertNotNull($request->attributes->get('invoicePortalPassphrase'));
    }

    private function createRequestWithSession(array $sessionData): Request
    {
        $request = Request::create('/', 'GET');
        $session = $this->app->make('session')->driver();
        foreach ($sessionData as $key => $value) {
            $session->put($key, $value);
        }
        $request->setLaravelSession($session);
        return $request;
    }

    public function test_allows_request_when_mode_is_optional_and_no_passphrase(): void
    {
        $request = $this->createRequestWithSession([]);

        $response = $this->middleware->handle(
            $request,
            fn () => new Response('ok'),
            'optional'
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_blocks_request_when_mode_is_required_and_no_passphrase(): void
    {
        $request = $this->createRequestWithSession([]);

        try {
            $this->middleware->handle(
                $request,
                fn () => new Response('ok'),
                'required'
            );
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
            $this->assertEquals('Passphrase portal invoice tidak valid atau sudah kedaluwarsa.', $e->getMessage());
        }
    }

    public function test_clears_session_when_passphrase_is_invalid(): void
    {
        $passphrase = InvoicePortalPassphrase::factory()->create([
            'is_active' => false,
        ]);

        $request = $this->createRequestWithSession([
            'invoice_portal_passphrase' => [
                'id' => $passphrase->id,
                'verified_at' => now()->toIso8601String(),
            ],
        ]);

        $this->middleware->handle(
            $request,
            fn () => new Response('ok'),
            'optional'
        );

        $this->assertNull($request->session()->get('invoice_portal_passphrase'));
    }

    public function test_clears_session_when_passphrase_is_expired(): void
    {
        $passphrase = InvoicePortalPassphrase::factory()->create([
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $request = $this->createRequestWithSession([
            'invoice_portal_passphrase' => [
                'id' => $passphrase->id,
                'verified_at' => now()->toIso8601String(),
            ],
        ]);

        $this->middleware->handle(
            $request,
            fn () => new Response('ok'),
            'optional'
        );

        $this->assertNull($request->session()->get('invoice_portal_passphrase'));
    }

    public function test_clears_session_when_verification_expired(): void
    {
        $passphrase = InvoicePortalPassphrase::factory()->create([
            'is_active' => true,
            'expires_at' => now()->addDay(),
        ]);

        $request = $this->createRequestWithSession([
            'invoice_portal_passphrase' => [
                'id' => $passphrase->id,
                'verified_at' => now()->subDays(2)->toIso8601String(),
            ],
        ]);

        $this->middleware->handle(
            $request,
            fn () => new Response('ok'),
            'optional'
        );

        $this->assertNull($request->session()->get('invoice_portal_passphrase'));
    }
}

