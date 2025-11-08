<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CategoryService();
    }

    public function test_delete_returns_false_when_category_has_transactions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        
        Transaction::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);

        $result = $this->service->delete($category);

        $this->assertFalse($result);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_delete_returns_true_and_deletes_category_when_no_transactions(): void
    {
        $category = Category::factory()->create();

        $result = $this->service->delete($category);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}

