<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\RecurringRevenue;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecurringRevenueController extends Controller
{
    public function index(): View
    {
        $revenues = RecurringRevenue::with('client')->get();
        $clients = Client::all();
        $categories = Category::all();
        return view('recurring_revenues.index', compact('revenues', 'clients', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'category_id' => 'nullable|exists:categories,id',
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric',
            'frequency' => 'required|string',
            'next_run' => 'required|date',
            'description' => 'nullable|string',
        ]);
        RecurringRevenue::create($data);
        return redirect()->route('recurring_revenues.index')->with('success', 'Pendapatan berulang ditambahkan.');
    }

    public function update(Request $request, RecurringRevenue $recurring_revenue): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'category_id' => 'nullable|exists:categories,id',
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric',
            'frequency' => 'required|string',
            'next_run' => 'required|date',
            'paused' => 'boolean',
            'description' => 'nullable|string',
        ]);
        $recurring_revenue->update($data);
        return redirect()->route('recurring_revenues.index')->with('success', 'Pendapatan berulang diperbarui.');
    }

    public function destroy(RecurringRevenue $recurring_revenue): RedirectResponse
    {
        $recurring_revenue->delete();
        return redirect()->route('recurring_revenues.index')->with('success', 'Pendapatan berulang dihapus.');
    }

    public function pause(RecurringRevenue $recurring_revenue): RedirectResponse
    {
        $recurring_revenue->update(['paused' => true]);
        return redirect()->route('recurring_revenues.index');
    }

    public function resume(RecurringRevenue $recurring_revenue): RedirectResponse
    {
        $recurring_revenue->update(['paused' => false]);
        return redirect()->route('recurring_revenues.index');
    }
}
