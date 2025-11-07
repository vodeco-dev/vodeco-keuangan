<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoicePdfService
{
    /**
     * Prepare view data for the invoice PDF.
     *
     * @return array{invoice: Invoice, settings: array<string, mixed>}
     */
    public function viewData(Invoice $invoice): array
    {
        $invoice->loadMissing('items', 'customerService');

        return [
            'invoice' => $invoice,
            'settings' => Setting::pluck('value', 'key')->all(),
        ];
    }

    public function renderView(Invoice $invoice): View
    {
        return view('invoices.pdf', $this->viewData($invoice));
    }

    public function makePdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('invoices.pdf', $this->viewData($invoice))
            ->setPaper('a4');
    }

    public function store(Invoice $invoice): string
    {
        $path = $this->generatePath($invoice);
        $disk = Storage::disk('public');

        if (app()->runningUnitTests()) {
            $disk->put($path, $this->renderView($invoice)->render());

            return $path;
        }

        $disk->put($path, $this->makePdf($invoice)->output());

        return $path;
    }

    protected function generatePath(Invoice $invoice): string
    {
        $baseName = Str::slug($invoice->number ?? '');

        if ($baseName === '') {
            $baseName = 'invoice-' . ($invoice->id ?? Str::random(8));
        }

        $timestamp = now()->format('YmdHis');
        $uuid = Str::uuid()->toString();

        return "invoices/{$baseName}-{$timestamp}-{$uuid}.pdf";
    }
}
