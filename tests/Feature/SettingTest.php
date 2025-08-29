<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_setting()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings', [
            'app_name' => 'KeuanganKu',
        ]);

        $response->assertRedirect('/settings');

        $this->assertDatabaseHas('settings', [
            'key' => 'app_name',
            'value' => 'KeuanganKu',
        ]);
    }
}
