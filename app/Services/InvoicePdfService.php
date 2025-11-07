<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class InvoicePdfService
{
    /**
     * Get PDF generation strategy from config.
     */
    protected function getStrategy(): string
    {
        return config('pdf.generation.strategy', 'on_demand');
    }

    /**
     * Check if PDF caching is enabled.
     */
    protected function isCacheEnabled(): bool
    {
        return config('pdf.cache.enabled', true);
    }

    /**
     * Get cache TTL in minutes.
     */
    protected function getCacheTtl(): int
    {
        return (int) config('pdf.cache.ttl', 1440);
    }

    /**
     * Get cache disk.
     */
    protected function getCacheDisk(): string
    {
        return config('pdf.cache.disk', 'public');
    }

    /**
     * Get cache path prefix.
     */
    protected function getCachePath(): string
    {
        return config('pdf.cache.path', 'invoices/cache');
    }
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

    /**
     * Store PDF temporarily in cache (with TTL).
     */
    public function storeInCache(Invoice $invoice): string
    {
        $path = $this->generateCachePath($invoice);
        $disk = Storage::disk($this->getCacheDisk());

        if (app()->runningUnitTests()) {
            $disk->put($path, $this->renderView($invoice)->render());
        } else {
            $disk->put($path, $this->makePdf($invoice)->output());
        }

        // Store metadata for cleanup
        $this->storeCacheMetadata($path);

        return $path;
    }

    /**
     * Store PDF permanently (legacy method for backward compatibility).
     */
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

    /**
     * Generate PDF and return as stream (no storage).
     */
    public function streamPdf(Invoice $invoice)
    {
        return $this->makePdf($invoice)->stream($invoice->number . '.pdf', [
            'Content-Disposition' => 'inline; filename="' . $invoice->number . '.pdf"',
        ]);
    }

    /**
     * Download PDF (generate on-the-fly).
     */
    public function downloadPdf(Invoice $invoice)
    {
        return $this->makePdf($invoice)->download($invoice->number . '.pdf');
    }

    /**
     * Ensure PDF is available (with caching support).
     */
    public function ensureStoredPdfPath(Invoice $invoice): string
    {
        $strategy = $this->getStrategy();

        // For on_demand strategy with caching enabled
        if ($strategy === 'on_demand' && $this->isCacheEnabled()) {
            return $this->ensureCachedPdfPath($invoice);
        }

        // For persistent strategy (legacy behavior)
        return $this->ensurePersistentPdfPath($invoice);
    }

    /**
     * Ensure PDF exists in cache, generate if needed.
     */
    protected function ensureCachedPdfPath(Invoice $invoice): string
    {
        $disk = Storage::disk($this->getCacheDisk());

        // Check if valid cache exists
        $cachedPath = $this->getCachedPath($invoice);
        if ($cachedPath && $disk->exists($cachedPath) && $this->isCacheValid($cachedPath)) {
            return $cachedPath;
        }

        // Clean up old cache if exists
        if ($cachedPath && $disk->exists($cachedPath)) {
            $disk->delete($cachedPath);
            $this->removeCacheMetadata($cachedPath);
        }

        // Generate new cache
        $newPath = $this->storeInCache($invoice);

        if (! $newPath) {
            throw new RuntimeException('Unable to cache invoice PDF.');
        }

        return $newPath;
    }

    /**
     * Ensure PDF is stored persistently (legacy behavior).
     */
    protected function ensurePersistentPdfPath(Invoice $invoice): string
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

    /**
     * Generate cache path for invoice PDF.
     */
    protected function generateCachePath(Invoice $invoice): string
    {
        $baseName = Str::slug($invoice->number ?? 'invoice-' . $invoice->id);
        $hash = md5($invoice->id . '-' . $invoice->updated_at->timestamp);

        return $this->getCachePath() . "/{$baseName}-{$hash}.pdf";
    }

    /**
     * Get cached PDF path for invoice.
     */
    protected function getCachedPath(Invoice $invoice): ?string
    {
        $baseName = Str::slug($invoice->number ?? 'invoice-' . $invoice->id);
        $hash = md5($invoice->id . '-' . $invoice->updated_at->timestamp);
        $path = $this->getCachePath() . "/{$baseName}-{$hash}.pdf";

        return $path;
    }

    /**
     * Check if cached PDF is still valid.
     */
    protected function isCacheValid(string $path): bool
    {
        $metadata = Cache::get('pdf_cache_metadata:' . md5($path));

        if (! $metadata || ! is_array($metadata)) {
            return false;
        }

        $createdAt = $metadata['created_at'] ?? 0;
        $ttl = $this->getCacheTtl() * 60; // Convert to seconds

        return (time() - $createdAt) < $ttl;
    }

    /**
     * Store cache metadata for cleanup purposes.
     */
    protected function storeCacheMetadata(string $path): void
    {
        $key = 'pdf_cache_metadata:' . md5($path);

        Cache::put($key, [
            'path' => $path,
            'created_at' => time(),
        ], now()->addMinutes($this->getCacheTtl() + 60)); // Add buffer
    }

    /**
     * Remove cache metadata.
     */
    protected function removeCacheMetadata(string $path): void
    {
        $key = 'pdf_cache_metadata:' . md5($path);
        Cache::forget($key);
    }

    /**
     * Invalidate cache for specific invoice.
     */
    public function invalidateCache(Invoice $invoice): void
    {
        $disk = Storage::disk($this->getCacheDisk());
        $cachedPath = $this->getCachedPath($invoice);

        if ($cachedPath && $disk->exists($cachedPath)) {
            $disk->delete($cachedPath);
            $this->removeCacheMetadata($cachedPath);
        }

        // Also clean up any old cache files for this invoice
        $baseName = Str::slug($invoice->number ?? 'invoice-' . $invoice->id);
        $pattern = $this->getCachePath() . "/{$baseName}-*.pdf";

        $files = $disk->files($this->getCachePath());
        foreach ($files as $file) {
            if (Str::startsWith(basename($file), $baseName . '-')) {
                $disk->delete($file);
                $this->removeCacheMetadata($file);
            }
        }
    }
}
