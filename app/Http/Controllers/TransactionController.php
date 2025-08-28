<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with('category')->get();
        $categories = Category::all();

        return view('transactions.index', compact('transactions', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'amount' => ['required', 'integer'],
            'description' => ['nullable', 'string'],
        ]);

        Transaction::create($validated);

        return redirect()->route('transactions.index');
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'amount' => ['required', 'integer'],
            'description' => ['nullable', 'string'],
        ]);

        $transaction->update($validated);

        return redirect()->route('transactions.index');
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return redirect()->route('transactions.index');
    }
}
