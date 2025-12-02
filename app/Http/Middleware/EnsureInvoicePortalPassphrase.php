<?php

namespace App\Http\Middleware;

use App\Models\InvoicePortalPassphrase;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EnsureInvoicePortalPassphrase
{
    public function handle(Request $request, Closure $next, string $mode = 'optional')
    {
        $session = (array) $request->session()->get('invoice_portal_passphrase', []);
        $passphraseId = $session['id'] ?? null;

        if (! empty($session['verified_at'])) {
            try {
                $verifiedAt = Carbon::parse($session['verified_at']);
            } catch (\Throwable $e) {
                $verifiedAt = null;
            }

            if ($verifiedAt && now()->greaterThan($verifiedAt->clone()->addDay())) {
                $request->session()->forget('invoice_portal_passphrase');
                $session = [];
                $passphraseId = null;
            }
        }

        $passphrase = null;
        if ($passphraseId) {
            $passphrase = InvoicePortalPassphrase::find($passphraseId);

            if (! $passphrase || ! $passphrase->isUsable()) {
                $request->session()->forget('invoice_portal_passphrase');
                $passphrase = null;
            }
        }

        if ($mode === 'required' && ! $passphrase) {
            abort(403, 'Passphrase portal invoice tidak valid atau sudah kedaluwarsa.');
        }

        if ($passphrase) {
            $request->attributes->set('invoicePortalPassphrase', $passphrase);
        }

        return $next($request);
    }
}
