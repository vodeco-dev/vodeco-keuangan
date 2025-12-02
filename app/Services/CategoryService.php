<?php

namespace App\Services;

use App\Models\Category;

class CategoryService
{
    public function delete(Category $category): bool
    {
        if ($category->transactions()->exists()) {
            return false;
        }

        $category->delete();

        return true;
    }
}
