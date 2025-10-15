<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePassThroughPackageRequest;
use App\Http\Requests\UpdatePassThroughPackageRequest;
use App\Models\PassThroughPackage;
use App\Models\Setting;
use App\Services\PassThroughPackageManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class PassThroughPackageController extends Controller
{
    private const INVOICE_CATEGORY_SETTING_KEY = 'pass_through_invoice_category_id';

    public function store(
        StorePassThroughPackageRequest $request,
        PassThroughPackageManager $manager
    ): RedirectResponse {
        $manager->store($request->validated());

        return redirect()
            ->route('debts.index')
            ->with('success', 'Paket Invoices Iklan berhasil ditambahkan.')
            ->with('open_pass_through_modal', true);
    }

    public function update(
        UpdatePassThroughPackageRequest $request,
        PassThroughPackageManager $manager,
        PassThroughPackage $package
    ): RedirectResponse {
        $manager->update($package, $request->validated());

        return redirect()
            ->route('debts.index')
            ->with('success', 'Paket Invoices Iklan berhasil diperbarui.')
            ->with('open_pass_through_modal', true);
    }

    public function destroy(PassThroughPackageManager $manager, PassThroughPackage $package): RedirectResponse
    {
        $manager->delete($package->uuid);

        return redirect()
            ->route('debts.index')
            ->with('success', 'Paket Invoices Iklan berhasil dihapus.')
            ->with('open_pass_through_modal', true);
    }

    public function updateInvoiceCategory(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('passThroughInvoiceCategory', [
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('type', 'pemasukan'),
            ],
        ]);

        $categoryId = $validated['category_id'] ?? null;

        if ($categoryId) {
            Setting::updateOrCreate(
                ['key' => self::INVOICE_CATEGORY_SETTING_KEY],
                ['value' => $categoryId]
            );
        } else {
            Setting::where('key', self::INVOICE_CATEGORY_SETTING_KEY)->delete();
        }

        Cache::forget('setting:' . self::INVOICE_CATEGORY_SETTING_KEY);

        return redirect()
            ->route('debts.index')
            ->with('success', 'Kategori pendapatan Invoices Iklan berhasil diperbarui.')
            ->with('open_pass_through_modal', true);
    }
}
