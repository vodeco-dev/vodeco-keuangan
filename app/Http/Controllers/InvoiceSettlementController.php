<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceSettlementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceSettlementController extends Controller
{
    public function show(string $token): View
    {
        $invoice = Invoice::where('settlement_token', $token)->first();

        if (! $invoice || ! $invoice->hasValidSettlementToken($token)) {
            abort(404);
        }

        return view('invoices.settlement.confirm', [
            'invoice' => $invoice,
            'token' => $token,
        ]);
    }

    public function store(Request $request, string $token, InvoiceSettlementService $service)
    {
        $invoice = Invoice::where('settlement_token', $token)->first();

        if (! $invoice || ! $invoice->hasValidSettlementToken($token)) {
            abort(404);
        }

        if ($invoice->status === 'lunas') {
            $service->revokeToken($invoice);
            $invoice->refresh();

            return view('invoices.settlement.success', [
                'invoice' => $invoice,
                'alreadySettled' => true,
            ]);
        }

        $service->confirmSettlement($invoice, $request->ip(), $request->fullUrl());

        $invoice->refresh();
        $service->revokeToken($invoice);
        $invoice->refresh();

        return view('invoices.settlement.success', [
            'invoice' => $invoice,
            'alreadySettled' => false,
        ]);
    }
}
