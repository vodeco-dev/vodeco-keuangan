<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_categories()
    {
        $response = $this->get('/categories');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_categories_page()
    {
        $user = User::factory()->create();
        Category::factory()->count(2)->create();

        $response = $this->actingAs($user)->get('/categories');
        $response->assertStatus(200);
    }

    public function test_store_requires_name()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/categories', []);
        $response->assertSessionHasErrors('name');
    }

    public function test_store_creates_category()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/categories', [
            'name' => 'Food',
        ]);

        $response->assertRedirect('/categories');
        $this->assertDatabaseHas('categories', [
            'name' => 'Food',
        ]);
    }

    public function test_update_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->put("/categories/{$category->id}", [
            'name' => 'Updated',
        ]);

        $response->assertRedirect('/categories');
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated',
        ]);
    }

    public function test_delete_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->delete("/categories/{$category->id}");

        $response->assertRedirect('/categories');
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }
}
