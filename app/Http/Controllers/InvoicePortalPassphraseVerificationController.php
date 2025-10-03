<?php

namespace App\Http\Controllers;

use App\Models\InvoicePortalPassphrase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

class InvoicePortalPassphraseVerificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'passphrase' => ['required', 'string'],
        ], [
            'passphrase.required' => 'Masukkan passphrase akses portal invoice.',
        ]);

        $passphraseInput = (string) $validated['passphrase'];

        $candidate = InvoicePortalPassphrase::query()
            ->where('is_active', true)
            ->get()
            ->first(function (InvoicePortalPassphrase $candidate) use ($passphraseInput) {
                if ($candidate->isExpired()) {
                    return false;
                }

                return Hash::check($passphraseInput, $candidate->passphrase_hash);
            });

        if (! $candidate) {
            return Redirect::back()
                ->withErrors(['passphrase' => 'Passphrase tidak ditemukan atau sudah tidak berlaku.'], 'passphraseVerification')
                ->withInput(['passphrase' => '']);
        }

        $sessionData = [
            'id' => $candidate->id,
            'token' => Crypt::encryptString((string) $candidate->id),
            'access_type' => $candidate->access_type->value,
            'access_label' => $candidate->access_type->label(),
            'verified_at' => now()->toIso8601String(),
        ];

        $request->session()->put('invoice_portal_passphrase', $sessionData);

        $candidate->markAsUsed($request->ip(), $request->userAgent(), 'verified');

        return Redirect::route('invoices.public.create')
            ->with('passphrase_verified', 'Passphrase berhasil diverifikasi untuk akses '.$candidate->access_type->label().'.');
    }
}
