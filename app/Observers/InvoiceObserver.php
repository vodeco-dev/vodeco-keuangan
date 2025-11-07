<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\Storage;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        // Generate PDF after invoice is created
        try {
            $pdfService = app(InvoicePdfService::class);
            $pdfPath = $pdfService->store($invoice);
            
            if ($pdfPath) {
                $invoice->forceFill(['pdf_path' => $pdfPath])->saveQuietly();
            }
        } catch (\Throwable $exception) {
            // Log error but don't block invoice creation
            \Log::error('Failed to generate PDF for newly created invoice', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function deleting(Invoice $invoice): void
    {
        // Clean up persistent PDF if exists
        $path = $invoice->pdf_path;

        if ($path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $exception) {
                // Silently ignore storage errors to avoid blocking invoice deletion.
            }
        }

        // Clean up cached PDF if exists
        try {
            app(InvoicePdfService::class)->invalidateCache($invoice);
        } catch (\Throwable $exception) {
            // Silently ignore cache errors to avoid blocking invoice deletion.
        }
    }

    public function updated(Invoice $invoice): void
    {
        // If invoice is updated and using on_demand strategy, invalidate cache
        if (config('pdf.generation.strategy') === 'on_demand' && config('pdf.cache.enabled')) {
            try {
                app(InvoicePdfService::class)->invalidateCache($invoice);
            } catch (\Throwable $exception) {
                // Silently ignore cache errors
            }
        }
    }
}
