<?php

namespace App\Services;

use App\Models\Category;

class CategoryService
{
    /**
     * Hapus kategori.
     *
     * @param Category $category
     * @return bool
     */
    public function delete(Category $category): bool
    {
        if ($category->transactions()->exists()) {
            return false;
        }

        $category->delete();

        return true;
    }
}
