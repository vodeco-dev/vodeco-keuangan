<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

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

    public function ensureStoredPdfPath(Invoice $invoice): string
    {
        $disk = Storage::disk('public');

        $currentPath = $invoice->pdf_path;

        if ($currentPath && $disk->exists($currentPath)) {
            return $currentPath;
        }

        if ($currentPath) {
            $disk->delete($currentPath);
        }

        $newPath = $this->store($invoice);

        if (! $newPath) {
            throw new RuntimeException('Unable to store invoice PDF.');
        }

        $invoice->pdf_path = $newPath;
        $invoice->saveQuietly();
        
        // Refresh to ensure we have the latest data from database
        $invoice->refresh();

        return $newPath;
    }

    public function ensureHostedUrl(Invoice $invoice): ?string
    {
        try {
            $path = $this->ensureStoredPdfPath($invoice);
        } catch (Throwable $exception) {
            return null;
        }

        try {
            $url = Storage::disk('public')->url($path);
        } catch (Throwable $exception) {
            return null;
        }

        if (! is_string($url) || $url === '') {
            return null;
        }

        return $url;
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
