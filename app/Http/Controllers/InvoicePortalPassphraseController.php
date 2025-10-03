<?php

namespace App\Http\Controllers;

use App\Enums\InvoicePortalPassphraseAccessType;
use App\Http\Requests\RotateInvoicePortalPassphraseRequest;
use App\Http\Requests\StoreInvoicePortalPassphraseRequest;
use App\Models\InvoicePortalPassphrase;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class InvoicePortalPassphraseController extends Controller
{
    public function index(): View
    {
        $passphrases = InvoicePortalPassphrase::query()
            ->with(['creator', 'deactivator'])
            ->latest()
            ->get();

        return view('invoice-portal.passphrases.index', [
            'passphrases' => $passphrases,
            'accessTypes' => InvoicePortalPassphraseAccessType::cases(),
        ]);
    }

    public function store(StoreInvoicePortalPassphraseRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $accessType = InvoicePortalPassphraseAccessType::from($validated['access_type']);
        $plainPassphrase = $validated['passphrase'] ?: Str::password(16, symbols: false);

        $passphrase = new InvoicePortalPassphrase([
            'public_id' => InvoicePortalPassphrase::makePublicId(),
            'access_type' => $accessType,
            'label' => trim((string) $validated['label']),
            'is_active' => true,
            'expires_at' => $validated['expires_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $passphrase->setPassphrase($plainPassphrase);
        $passphrase->save();

        $passphrase->logs()->create([
            'action' => 'generated',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return Redirect::route('invoice-portal.passphrases.index')
            ->with('passphrase_plain', [
                'value' => $plainPassphrase,
                'label' => $passphrase->displayLabel(),
            ])
            ->with('status', 'Passphrase portal invoice baru berhasil dibuat.');
    }

    public function rotate(InvoicePortalPassphrase $passphrase, RotateInvoicePortalPassphraseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! $passphrase->is_active) {
            return Redirect::back()->withErrors([
                'passphrase_'.$passphrase->id => 'Passphrase sudah tidak aktif.',
            ]);
        }

        $plainPassphrase = $validated['passphrase'] ?: Str::password(16, symbols: false);

        $passphrase->setPassphrase($plainPassphrase);
        if (array_key_exists('label', $validated) && $validated['label'] !== null) {
            $passphrase->label = trim((string) $validated['label']);
        }
        $passphrase->expires_at = $validated['expires_at'] ?? $passphrase->expires_at;
        $passphrase->save();

        $passphrase->logs()->create([
            'action' => 'rotated',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return Redirect::route('invoice-portal.passphrases.index')
            ->with('passphrase_plain', [
                'value' => $plainPassphrase,
                'label' => $passphrase->displayLabel(),
            ])
            ->with('status', 'Passphrase berhasil diperbarui. Pastikan segera dibagikan ke pihak terkait.');
    }

    public function deactivate(InvoicePortalPassphrase $passphrase): RedirectResponse
    {
        if (! $passphrase->is_active) {
            return Redirect::back()->with('status', 'Passphrase sudah tidak aktif.');
        }

        $passphrase->forceFill([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_by' => auth()->id(),
        ])->save();

        $passphrase->logs()->create([
            'action' => 'deactivated',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return Redirect::back()->with('status', 'Passphrase berhasil dinonaktifkan.');
    }
}
