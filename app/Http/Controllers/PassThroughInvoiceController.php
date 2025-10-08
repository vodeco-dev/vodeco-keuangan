<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StorePassThroughInvoiceRequest;
use App\Models\Debt;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\PassThroughPackageManager;
use App\Support\PassThroughPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PassThroughInvoiceController extends Controller
{
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

        $invoice = DB::transaction(function () use ($request, $package, $data, $remaining) {
            $number = $this->generateInvoiceNumber();
            $user = $request->user();
            $issueDate = now();

            $invoice = Invoice::create([
                'user_id' => $user?->id,
                'created_by' => $user?->id,
                'customer_service_id' => null,
                'customer_service_name' => $user?->name,
                'client_name' => $data['client_name'],
                'client_whatsapp' => $data['client_whatsapp'],
                'client_address' => $data['client_address'] ?? null,
                'number' => $number,
                'issue_date' => $issueDate,
                'due_date' => $data['due_date'] ?? null,
                'status' => 'belum bayar',
                'total' => $package->packagePrice,
                'type' => $package->customerType === PassThroughPackage::CUSTOMER_TYPE_NEW
                    ? Invoice::TYPE_PASS_THROUGH_NEW
                    : Invoice::TYPE_PASS_THROUGH_EXISTING,
                'reference_invoice_id' => null,
                'down_payment' => 0,
                'down_payment_due' => null,
                'payment_date' => null,
            ]);

            $items = $this->makeInvoiceItems($package, $remaining);

            foreach ($items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'category_id' => null,
                    'description' => $item['description'],
                    'quantity' => 1,
                    'price' => $item['amount'],
                ]);
            }

            Debt::create([
                'user_id' => $user?->id,
                'invoice_id' => $invoice->id,
                'description' => $invoice->transactionDescription(),
                'related_party' => $invoice->client_name ?: $invoice->client_whatsapp,
                'type' => Debt::TYPE_PASS_THROUGH,
                'amount' => $remaining,
                'due_date' => $data['due_date'] ?? null,
                'status' => Debt::STATUS_BELUM_LUNAS,
                'daily_deduction' => $package->dailyDeduction,
            ]);

            return $invoice;
        });

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

    protected function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $count = Invoice::whereDate('created_at', today())->count();
        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        return "{$date}-{$sequence}";
    }

    protected function makeInvoiceItems(PassThroughPackage $package, float $remaining): array
    {
        $items = [];

        if ($package->customerType === PassThroughPackage::CUSTOMER_TYPE_NEW && $package->accountCreationFee > 0) {
            $items[] = [
                'description' => 'Biaya Pembuatan Akun Iklan',
                'amount' => $package->accountCreationFee,
            ];
        }

        if ($package->maintenanceFee > 0) {
            $items[] = [
                'description' => 'Jasa Maintenance',
                'amount' => $package->maintenanceFee,
            ];
        }

        if ($package->renewalFee > 0) {
            $items[] = [
                'description' => 'Biaya Perpanjangan',
                'amount' => $package->renewalFee,
            ];
        }

        $items[] = [
            'description' => 'Dana Pass Through',
            'amount' => $remaining,
        ];

        return $items;
    }
}
