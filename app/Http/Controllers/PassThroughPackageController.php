<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePassThroughPackageRequest;
use App\Http\Requests\UpdatePassThroughPackageRequest;
use App\Models\Setting;
use App\Services\PassThroughPackageManager;
use App\Support\PassThroughPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PassThroughPackageController extends Controller
{
    private const INVOICE_CATEGORY_SETTING_KEY = 'pass_through_invoice_category_id';

    public function store(
        Request $request,
        PassThroughPackageManager $manager
    ): RedirectResponse {
        if ($request->isMethod('get')) {
            \Illuminate\Support\Facades\Log::warning('PassThroughPackageController@store was accessed with a GET request.');
            return redirect()
                ->route('debts.index')
                ->withErrors(['get_request_error' => 'Terjadi kesalahan, permintaan tidak seharusnya dikirim dengan metode GET.'])
                ->with('open_pass_through_modal', true);
        }

        $validated = app(StorePassThroughPackageRequest::class)->validated();

        $data = $validated;
        $data['id'] = (string) Str::uuid();

        $manager->save(PassThroughPackage::fromArray($data));

        return redirect()
            ->route('debts.index')
            ->with('success', 'Paket Invoices Iklan berhasil ditambahkan.')
            ->with('open_pass_through_modal', true);
    }

    public function update(
        UpdatePassThroughPackageRequest $request,
        PassThroughPackageManager $manager,
        string $package
    ): RedirectResponse {
        $existing = $manager->find($package);

        if (! $existing) {
            return redirect()
                ->route('debts.index')
                ->withErrors([
                    'package_id' => 'Paket Invoices Iklan tidak ditemukan.',
                ], 'passThroughPackageUpdate')
                ->with('open_pass_through_modal', true)
                ->withInput();
        }

        $data = $request->validated();
        $data['id'] = $existing->id;

        $manager->save(PassThroughPackage::fromArray($data));

        return redirect()
            ->route('debts.index')
            ->with('success', 'Paket Invoices Iklan berhasil diperbarui.')
            ->with('open_pass_through_modal', true);
    }

    public function destroy(PassThroughPackageManager $manager, string $package): RedirectResponse
    {
        $manager->delete($package);

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
