<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(protected CategoryService $categoryService)
    {
        $this->authorizeResource(Category::class, 'category');
    }

    public function index(Request $request): View|JsonResponse
    {
        $categories = Cache::rememberForever('categories.grouped', function () {
            return Category::orderBy('name')->get()->groupBy('type');
        });

        $pemasukan = $categories->get('pemasukan', collect());
        $pengeluaran = $categories->get('pengeluaran', collect());

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess([
                'pemasukan' => $pemasukan->values(),
                'pengeluaran' => $pengeluaran->values(),
            ]);
        }

        return view('categories.index', compact('pemasukan', 'pengeluaran'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse|JsonResponse
    {
        $category = Category::create($request->validated());

        Cache::forget('categories');
        Cache::forget('income_categories');
        Cache::forget('expense_categories');
        Cache::forget('categories.grouped');

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess($category, 'Kategori baru berhasil ditambahkan.', 201);
        }

        return redirect()->route('categories.index')->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse|JsonResponse
    {
        $category->update($request->validated());

        Cache::forget('categories');
        Cache::forget('income_categories');
        Cache::forget('expense_categories');
        Cache::forget('categories.grouped');

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess($category, 'Kategori berhasil diperbarui.');
        }

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        $success = $this->categoryService->delete($category);

        if (!$success) {
            if ($this->isApiRequest($request)) {
                return $this->apiError('Kategori "' . $category->name . '" tidak dapat dihapus karena masih digunakan oleh transaksi.', 422);
            }
            return redirect()->route('categories.index')
                ->with('error', 'Kategori "' . $category->name . '" tidak dapat dihapus karena masih digunakan oleh transaksi.');
        }

        Cache::forget('categories');
        Cache::forget('income_categories');
        Cache::forget('expense_categories');
        Cache::forget('categories.grouped');

        if ($this->isApiRequest($request)) {
            return $this->apiSuccess(null, 'Kategori berhasil dihapus.');
        }

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
