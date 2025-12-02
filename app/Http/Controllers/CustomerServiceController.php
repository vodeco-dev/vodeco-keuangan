<?php

namespace App\Http\Controllers;

use App\Models\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerServiceController extends Controller
{
    public function create(Request $request): View
    {
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $allowedSortColumns = ['created_at', 'updated_at', 'name'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';
        
        $customerServices = CustomerService::orderBy($sortBy, $sortOrder)
            ->paginate(10)
            ->appends($request->query());

        return view('customer-services.create', compact('customerServices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        CustomerService::create($data);

        return redirect()
            ->route('customer-services.create')
            ->with('status', 'Customer service berhasil ditambahkan.');
    }
}
