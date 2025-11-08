<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AccessCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccessCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_history_tab_without_access_code(): void
    {
        $user = User::factory()->create(['role' => Role::STAFF]);
        $this->actingAs($user);

        $response = $this->get(route('invoices.index'));
        $response->assertOk();
        $tabStates = $response->viewData('tabStates');
        $this->assertTrue($tabStates['history']['unlocked']);
        $this->assertFalse($tabStates['history']['requires_code']);
    }

    public function test_accountant_can_view_settlement_tab_without_access_code(): void
    {
        $user = User::factory()->create(['role' => Role::ACCOUNTANT]);
        $this->actingAs($user);

        $response = $this->get(route('invoices.index'));
        $response->assertOk();
        $tabStates = $response->viewData('tabStates');
        $this->assertTrue($tabStates['settlement']['unlocked']);
        $this->assertFalse($tabStates['settlement']['requires_code']);
        $this->assertSame('Pelunasan', $tabStates['settlement']['label']);
    }

    public function test_accountant_can_verify_access_code(): void
    {
        $user = User::factory()->create(['role' => Role::ACCOUNTANT]);
        $publicId = (string) Str::uuid();
        $rawCode = 'ACCOUNTING-001';

        $accessCode = AccessCode::create([
            'public_id' => $publicId,
            'user_id' => $user->id,
            'role' => Role::ACCOUNTANT,
            'code_hash' => Hash::make($rawCode),
        ]);

        $this->actingAs($user);

        $this->post(route('access-codes.verify'), ['code' => $publicId . ':' . $rawCode])
            ->assertRedirect(route('invoices.index'));

        $this->assertNotNull($accessCode->fresh()->used_at);
        $this->assertContains(Role::ACCOUNTANT->value, session('verified_access_roles'));
    }
}
