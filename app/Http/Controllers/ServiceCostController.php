<?php

namespace App\Http\Controllers;

use App\Models\ServiceCost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceCostController extends Controller
{
    /**
     * Display a listing of the service costs.
     */
    public function index(): View
    {
        $serviceCosts = ServiceCost::orderBy('name')->get();

        return view('service_costs.index', compact('serviceCosts'));
    }

    /**
     * Store a newly created service cost in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:service_costs,name',
        ]);

        ServiceCost::create($validated);

        return redirect()->route('service_costs.index')
            ->with('success', 'Service cost berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified service cost.
     */
    public function edit(ServiceCost $serviceCost): View
    {
        return view('service_costs.edit', compact('serviceCost'));
    }

    /**
     * Update the specified service cost in storage.
     */
    public function update(Request $request, ServiceCost $serviceCost): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:service_costs,name,' . $serviceCost->id,
        ]);

        $serviceCost->update($validated);

        return redirect()->route('service_costs.index')
            ->with('success', 'Service cost berhasil diperbarui.');
    }

    /**
     * Remove the specified service cost from storage.
     */
    public function destroy(ServiceCost $serviceCost): RedirectResponse
    {
        if ($serviceCost->transactions()->exists()) {
            return redirect()->route('service_costs.index')
                ->with('error', 'Service cost tidak dapat dihapus karena masih digunakan oleh transaksi.');
        }

        $serviceCost->delete();

        return redirect()->route('service_costs.index')
            ->with('success', 'Service cost berhasil dihapus.');
    }
}
