<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StorePassThroughInvoiceRequest;
use App\Services\PassThroughInvoiceCreator;
use App\Services\PassThroughPackageManager;
use App\Support\PassThroughPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PassThroughInvoiceController extends Controller
{
    public function __construct(private PassThroughInvoiceCreator $creator)
    {
    }

    public function create(Request $request, PassThroughPackageManager $manager): View
    {
        $this->authorizeAccess($request->user()?->role);

        $packages = $manager->all();
        $packagesByType = $packages
            ->groupBy(fn (PassThroughPackage $package) => $package->customerType)
            ->map(fn ($group) => $group->map(fn (PassThroughPackage $package) => $package->toArray())->values())
            ->toArray();
        $packagesById = $packages
            ->mapWithKeys(fn (PassThroughPackage $package) => [
                $package->id => $package->toArray(),
            ])
            ->toArray();

        return view('invoices.pass-through.create', [
            'packagesByType' => $packagesByType,
            'packagesById' => $packagesById,
        ]);
    }

    public function store(
        StorePassThroughInvoiceRequest $request,
        PassThroughPackageManager $manager
    ): RedirectResponse {
        $this->authorizeAccess($request->user()?->role);

        $data = $request->validated();
        $package = $manager->find($data['package_id']);

        if (! $package || $package->customerType !== $data['customer_type']) {
            return back()
                ->withErrors(['package_id' => 'Paket pass through tidak valid untuk jenis pelanggan yang dipilih.'])
                ->withInput();
        }

        $remaining = $package->remainingPassThroughAmount();

        if ($remaining <= 0) {
            return back()
                ->withErrors(['package_id' => 'Konfigurasi paket menghasilkan nilai pass through yang tidak valid.'])
                ->withInput();
        }

        $user = $request->user();

        $invoice = $this->creator->create($package, [
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
