<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    /**
     * Menampilkan halaman daftar kategori, dipisahkan berdasarkan tipe.
     */
    public function index(): View
    {
        // Mengambil semua kategori dan mengelompokkannya berdasarkan 'type'
        $categories = Category::orderBy('name')->get()->groupBy('type');

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

        return redirect()->route('categories.index')->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    /**
     * Memperbarui kategori yang sudah ada.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Menghapus kategori dari database.
     */
    public function destroy(Category $category): RedirectResponse
    {
        // Mencegah penghapusan jika kategori masih digunakan oleh transaksi
        if ($category->transactions()->exists()) {
            return redirect()->route('categories.index')
                ->with('error', 'Kategori "' . $category->name . '" tidak dapat dihapus karena masih digunakan oleh transaksi.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
