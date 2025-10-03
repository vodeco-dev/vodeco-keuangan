<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AccessCode;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccessCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_service_requires_access_code_before_viewing_tabs(): void
    {
        $user = User::factory()->create(['role' => Role::CUSTOMER_SERVICE]);
        Invoice::factory()->create(['user_id' => $user->id, 'down_payment_due' => 100000, 'down_payment' => 0]);

        $publicId = (string) Str::uuid();
        $rawCode = 'CS-UNIT-001';

        AccessCode::create([
            'public_id' => $publicId,
            'user_id' => $user->id,
            'role' => Role::CUSTOMER_SERVICE,
            'code_hash' => Hash::make($rawCode),
        ]);

        $this->actingAs($user);

        $this->get(route('invoices.index'))
            ->assertOk()
            ->assertDontSee('Down Payment');

        $this->post(route('access-codes.verify'), ['code' => $publicId . ':' . $rawCode])
            ->assertRedirect(route('invoices.index'));

        $response = $this->get(route('invoices.index'));
        $response->assertOk();
        $tabStates = $response->viewData('tabStates');
        $this->assertTrue($tabStates['down-payment']['unlocked']);
        $this->assertTrue($tabStates['pay-in-full']['unlocked']);
    }

    public function test_settlement_admin_needs_access_code_for_pelunasan_tab(): void
    {
        $user = User::factory()->create(['role' => Role::SETTLEMENT_ADMIN]);
        Invoice::factory()->create(['user_id' => $user->id, 'status' => 'belum lunas']);

        $publicId = (string) Str::uuid();
        $rawCode = 'PELUNASAN-UNIT-1';

        AccessCode::create([
            'public_id' => $publicId,
            'user_id' => $user->id,
            'role' => Role::SETTLEMENT_ADMIN,
            'code_hash' => Hash::make($rawCode),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('invoices.index'));
        $response->assertOk();
        $this->assertFalse($response->viewData('tabStates')['settlement']['unlocked']);

        $this->post(route('access-codes.verify'), ['code' => $publicId . ':' . $rawCode])
            ->assertRedirect(route('invoices.index'));

        $this->assertEquals(['settlement_admin'], session('verified_access_roles'));
        $this->assertTrue(Gate::forUser($user)->allows('viewSettlementTab', Invoice::class));

        $response = $this->get(route('invoices.index'));

        $response->assertOk();
        $tabStates = $response->viewData('tabStates');
        $this->assertTrue($tabStates['settlement']['unlocked']);
        $this->assertSame('Pelunasan', $tabStates['settlement']['label']);
    }

    public function test_admin_can_view_tabs_without_access_code(): void
    {
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        Invoice::factory()->create(['user_id' => $admin->id, 'down_payment_due' => 50000, 'down_payment' => 0]);

        $this->actingAs($admin);

        $response = $this->get(route('invoices.index'));
        $response->assertOk();
        $tabStates = $response->viewData('tabStates');
        $this->assertTrue($tabStates['down-payment']['unlocked']);
        $this->assertTrue($tabStates['pay-in-full']['unlocked']);
        $this->assertTrue($tabStates['settlement']['unlocked']);
    }
}
