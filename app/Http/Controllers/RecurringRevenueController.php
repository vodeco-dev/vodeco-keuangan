<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecurringRevenueRequest;
use App\Http\Requests\UpdateRecurringRevenueRequest;
use App\Models\RecurringRevenue;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecurringRevenueController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(RecurringRevenue::class, 'recurring_revenue');
    }
    public function index(): View
    {
        $revenues = RecurringRevenue::all();
        $categories = Category::all();
        return view('recurring_revenues.index', compact('revenues', 'categories'));
    }

    public function store(StoreRecurringRevenueRequest $request): RedirectResponse
    {
        RecurringRevenue::create($request->validated());
        return redirect()->route('recurring_revenues.index')->with('success', 'Pendapatan berulang ditambahkan.');
    }

    public function update(UpdateRecurringRevenueRequest $request, RecurringRevenue $recurring_revenue): RedirectResponse
    {
        $recurring_revenue->update($request->validated());
        return redirect()->route('recurring_revenues.index')->with('success', 'Pendapatan berulang diperbarui.');
    }

    public function destroy(RecurringRevenue $recurring_revenue): RedirectResponse
    {
        $recurring_revenue->delete();
        return redirect()->route('recurring_revenues.index')->with('success', 'Pendapatan berulang dihapus.');
    }

    public function pause(RecurringRevenue $recurring_revenue): RedirectResponse
    {
        $this->authorize('update', $recurring_revenue);
        $recurring_revenue->update(['paused' => true]);
        return redirect()->route('recurring_revenues.index');
    }

    public function resume(RecurringRevenue $recurring_revenue): RedirectResponse
    {
        $this->authorize('update', $recurring_revenue);
        $recurring_revenue->update(['paused' => false]);
        return redirect()->route('recurring_revenues.index');
    }
}
