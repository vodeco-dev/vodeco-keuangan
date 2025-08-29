<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Transaction::class, 'transaction');
    }

    public function index(Request $request): View
    {
        $transactionsQuery = Transaction::with('category')
            ->where('user_id', $request->user()->id)
            ->latest('date');

        if ($request->filled('search')) {
            $transactionsQuery->where('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('date')) {
            $transactionsQuery->whereDate('date', $request->date);
        }

        if ($request->filled('category_id')) {
            $transactionsQuery->where('category_id', $request->category_id);
        }

        if ($request->filled('type')) {
            $transactionsQuery->whereHas('category', function ($query) use ($request) {
                $query->where('type', $request->type);
            });
        }

        $transactions = $transactionsQuery->paginate(10);

        $categories = Category::orderBy('name')->get();

        $totalPemasukan = Transaction::where('user_id', $request->user()->id)
            ->whereHas('category', function ($query) {
                $query->where('type', 'pemasukan');
            })->sum('amount');

        $totalPengeluaran = Transaction::where('user_id', $request->user()->id)
            ->whereHas('category', function ($query) {
                $query->where('type', 'pengeluaran');
            })->sum('amount');

        $saldo = $totalPemasukan - $totalPengeluaran;

        return view('transactions.index', compact('transactions', 'categories', 'totalPemasukan', 'totalPengeluaran', 'saldo'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        return view('transactions.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'date' => 'required|date',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        Transaction::create($request->all() + ['user_id' => $request->user()->id]);

        return redirect()->route('transactions.index')
                         ->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function edit(Transaction $transaction): View
    {
        $categories = Category::orderBy('type')->orderBy('name')->get()->groupBy('type');
        return view('transactions.edit', compact('transaction', 'categories'));
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $request->validate([
            'date' => 'required|date',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $transaction->update($request->all());

        return redirect()->route('transactions.index')
                         ->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return redirect()->route('transactions.index')
                         ->with('success', 'Transaksi berhasil dihapus.');
    }
}
