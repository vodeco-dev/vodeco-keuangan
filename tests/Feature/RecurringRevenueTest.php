<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RecurringRevenue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringRevenueTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_recurring_revenues()
    {
        $response = $this->get('/recurring_revenues');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_create_recurring_revenue()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post('/recurring_revenues', [
            'category_id' => $category->id,
            'user_id' => $user->id,
            'amount' => 1000,
            'frequency' => 'monthly',
            'next_run' => now()->addMonth()->toDateString(),
            'description' => 'Langganan',
        ]);

        $response->assertRedirect('/recurring_revenues');
        $this->assertDatabaseHas('recurring_revenues', [
            'category_id' => $category->id,
            'user_id' => $user->id,
            'amount' => 1000,
            'frequency' => 'monthly',
            'description' => 'Langganan',
        ]);
    }

    public function test_user_can_pause_and_resume_recurring_revenue()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $revenue = RecurringRevenue::create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'amount' => 2000,
            'frequency' => 'monthly',
            'next_run' => now()->addMonth()->toDateString(),
            'paused' => false,
        ]);

        $this->actingAs($user)->patch(route('recurring_revenues.pause', $revenue));
        $this->assertDatabaseHas('recurring_revenues', [
            'id' => $revenue->id,
            'paused' => true,
        ]);

        $this->actingAs($user)->patch(route('recurring_revenues.resume', $revenue));
        $this->assertDatabaseHas('recurring_revenues', [
            'id' => $revenue->id,
            'paused' => false,
        ]);
    }
}
