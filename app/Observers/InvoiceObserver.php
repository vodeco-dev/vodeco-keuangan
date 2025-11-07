<?php

namespace App\Observers;

use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;

class InvoiceObserver
{
    public function deleting(Invoice $invoice): void
    {
        $path = $invoice->pdf_path;

        if (! $path) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $exception) {
            // Silently ignore storage errors to avoid blocking invoice deletion.
        }
    }
}
