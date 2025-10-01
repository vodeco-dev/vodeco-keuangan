<?php

namespace App\Http\Controllers;

use App\Models\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerServiceController extends Controller
{
    public function create(): View
    {
        $customerServices = CustomerService::orderBy('name')->paginate(10);

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
