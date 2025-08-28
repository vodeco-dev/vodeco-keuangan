<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function index()
    {
        $debts = Debt::orderBy('due_date')->get();
        return view('debts.index', compact('debts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'creditor' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'due_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        Debt::create($validated);

        return redirect()->route('debts.index');
    }

    public function update(Request $request, Debt $debt)
    {
        $validated = $request->validate([
            'creditor' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'due_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $debt->update($validated);

        return redirect()->route('debts.index');
    }

    public function destroy(Debt $debt)
    {
        $debt->delete();

        return redirect()->route('debts.index');
    }
}

