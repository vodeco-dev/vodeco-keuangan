<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Terapkan authorization policy
     */
    public function __construct(protected CategoryService $categoryService)
    {
        $this->authorizeResource(Category::class, 'category');
    }

    /**
     * Menampilkan halaman daftar kategori, dipisahkan berdasarkan tipe.
     */
    public function index(): View
    {
        // Mengambil semua kategori dan mengelompokkannya berdasarkan 'type'
        $categories = Cache::rememberForever('categories.grouped', function () {
            return Category::orderBy('name')->get()->groupBy('type');
        });

        // Memisahkan koleksi menjadi pemasukan dan pengeluaran
        $pemasukan = $categories->get('pemasukan', collect());
        $pengeluaran = $categories->get('pengeluaran', collect());

        return view('categories.index', compact('pemasukan', 'pengeluaran'));
    }

    /**
     * Menyimpan kategori baru ke database.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        Cache::forget('categories');
        Cache::forget('categories.grouped');

        return redirect()->route('categories.index')->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    /**
     * Memperbarui kategori yang sudah ada.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        Cache::forget('categories');
        Cache::forget('categories.grouped');

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Menghapus kategori dari database.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $success = $this->categoryService->delete($category);

        if (!$success) {
            return redirect()->route('categories.index')
                ->with('error', 'Kategori "' . $category->name . '" tidak dapat dihapus karena masih digunakan oleh transaksi.');
        }

        Cache::forget('categories');
        Cache::forget('categories.grouped');

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
