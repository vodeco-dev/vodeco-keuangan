<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\Storage;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        try {
            $pdfService = app(InvoicePdfService::class);
            $pdfPath = $pdfService->store($invoice);
            
            if ($pdfPath) {
                $invoice->forceFill(['pdf_path' => $pdfPath])->saveQuietly();
            }
        } catch (\Throwable $exception) {
            \Log::error('Failed to generate PDF for newly created invoice', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function deleting(Invoice $invoice): void
    {
        $path = $invoice->pdf_path;

        if ($path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $exception) {
            }
        }

        try {
            app(InvoicePdfService::class)->invalidateCache($invoice);
        } catch (\Throwable $exception) {
        }
    }

    public function updated(Invoice $invoice): void
    {
        if (config('pdf.generation.strategy') === 'on_demand' && config('pdf.cache.enabled')) {
            try {
                app(InvoicePdfService::class)->invalidateCache($invoice);
            } catch (\Throwable $exception) {
            }
        }
    }
}
