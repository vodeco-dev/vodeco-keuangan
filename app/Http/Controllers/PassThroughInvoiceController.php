<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StorePassThroughInvoiceRequest;
use App\Services\PassThroughInvoiceCreator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PassThroughInvoiceController extends Controller
{
    public function __construct(private PassThroughInvoiceCreator $creator)
    {
    }

    public function create(Request $request): View
    {
        $this->authorizeAccess($request->user()?->role);

        return view('invoices.pass-through.create');
    }

    public function store(
        StorePassThroughInvoiceRequest $request
    ): RedirectResponse {
        $this->authorizeAccess($request->user()?->role);

        $data = $request->validated();

        $user = $request->user();

        try {
            $invoice = $this->creator->create([
                'customer_type' => $data['customer_type'],
                'daily_balance' => $data['daily_balance'],
                'estimated_duration' => $data['estimated_duration'],
                'maintenance_fee' => $data['maintenance_fee'],
                'account_creation_fee' => $data['account_creation_fee'] ?? 0,
                'owner_id' => $user?->id,
                'created_by' => $user?->id,
                'customer_service_id' => null,
                'customer_service_name' => $user?->name,
                'client_name' => $data['client_name'],
                'client_whatsapp' => $data['client_whatsapp'],
                'client_address' => $data['client_address'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'debt_user_id' => $user?->id,
            ]);
        } catch (\RuntimeException $exception) {
            return back()
                ->withErrors(['daily_balance' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice pass through berhasil dibuat dengan nomor ' . $invoice->number . '.');
    }

    protected function authorizeAccess(?Role $role): void
    {
        if (! $role) {
            abort(403);
        }

        if ($role !== Role::STAFF && $role !== Role::ADMIN) {
            abort(403);
        }
    }
}
