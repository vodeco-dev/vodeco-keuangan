<?php

namespace App\Http\Controllers;

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
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'type' => 'required|in:pemasukan,pengeluaran',
        ]);

        Category::create($request->all());

        return redirect()->route('categories.index')->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    /**
     * Memperbarui kategori yang sudah ada.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'type' => 'required|in:pemasukan,pengeluaran',
        ]);

        $category->update($request->all());

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
