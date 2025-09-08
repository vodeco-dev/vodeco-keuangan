<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisplayPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_theme_preference()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/display', [
            'theme' => 'dark',
        ]);

        $response->assertRedirect('/settings/display');

        $this->assertDatabaseHas('settings', [
            'key' => 'theme',
            'value' => 'dark',
        ]);
    }
}
