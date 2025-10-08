<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePassThroughPackageRequest;
use App\Http\Requests\UpdatePassThroughPackageRequest;
use App\Services\PassThroughPackageManager;
use App\Support\PassThroughPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class PassThroughPackageController extends Controller
{
    public function store(
        StorePassThroughPackageRequest $request,
        PassThroughPackageManager $manager
    ): RedirectResponse {
        $data = $request->validated();
        $data['id'] = (string) Str::uuid();

        $manager->save(PassThroughPackage::fromArray($data));

        return redirect()
            ->route('debts.index')
            ->with('success', 'Paket pass through berhasil ditambahkan.')
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
                    'package_id' => 'Paket pass through tidak ditemukan.',
                ], 'passThroughPackageUpdate')
                ->with('open_pass_through_modal', true)
                ->withInput();
        }

        $data = $request->validated();
        $data['id'] = $existing->id;

        $manager->save(PassThroughPackage::fromArray($data));

        return redirect()
            ->route('debts.index')
            ->with('success', 'Paket pass through berhasil diperbarui.')
            ->with('open_pass_through_modal', true);
    }

    public function destroy(PassThroughPackageManager $manager, string $package): RedirectResponse
    {
        $manager->delete($package);

        return redirect()
            ->route('debts.index')
            ->with('success', 'Paket pass through berhasil dihapus.')
            ->with('open_pass_through_modal', true);
    }
}
