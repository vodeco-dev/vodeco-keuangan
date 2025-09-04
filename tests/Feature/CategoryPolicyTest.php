<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware; // Bisa dihapus jika tidak digunakan
use App\Http\Middleware\VerifyCsrfToken; // Tambahkan ini jika menggunakan withoutMiddleware yang spesifik
use Tests\TestCase;

class CategoryPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat pengguna dan kategori untuk pengujian
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    public function test_authenticated_user_can_view_any_categories(): void
    {
        $response = $this->actingAs($this->user)->get(route('categories.index'));
        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_create_category(): void
    {
        // Nonaktifkan CSRF protection untuk test ini
        $this->withoutMiddleware([VerifyCsrfToken::class]);

        $response = $this->actingAs($this->user)->post(route('categories.store'), [
            'name' => 'Kategori Baru',
            'type' => 'income',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Kategori Baru']);
    }

    public function test_authenticated_user_can_update_category(): void
    {
        // Nonaktifkan CSRF protection untuk test ini
        $this->withoutMiddleware([VerifyCsrfToken::class]);
        
        $response = $this->actingAs($this->user)->put(route('categories.update', $this->category), [
            'name' => 'Kategori Diperbarui',
            'type' => $this->category->type,
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Kategori Diperbarui']);
    }

    public function test_authenticated_user_can_delete_category(): void
    {
        // Nonaktifkan CSRF protection untuk test ini
        $this->withoutMiddleware([VerifyCsrfToken::class]);
        
        $response = $this->actingAs($this->user)->delete(route('categories.destroy', $this->category));

        $response->assertRedirect(route('categories.index'));
        $this->assertModelMissing($this->category);
    }
}
