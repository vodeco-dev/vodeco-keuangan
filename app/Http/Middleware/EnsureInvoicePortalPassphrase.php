<?php

namespace App\Http\Middleware;

use App\Models\InvoicePortalPassphrase;
use Closure;
use Illuminate\Http\Request;

class EnsureInvoicePortalPassphrase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $mode = 'optional')
    {
        $session = (array) $request->session()->get('invoice_portal_passphrase', []);
        $passphraseId = $session['id'] ?? null;

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
