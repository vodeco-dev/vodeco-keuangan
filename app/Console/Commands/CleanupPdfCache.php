<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CleanupPdfCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:cleanup-cache
                          {--force : Force cleanup all cached PDFs regardless of TTL}
                          {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired PDF cache files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! config('pdf.cache.enabled')) {
            $this->info('PDF caching is disabled. Nothing to cleanup.');
            return Command::SUCCESS;
        }

        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');

        $diskName = config('pdf.cache.disk', 'public');
        $cachePath = config('pdf.cache.path', 'invoices/cache');
        $ttl = config('pdf.cache.ttl', 1440) * 60; // Convert to seconds

        $this->info("Starting PDF cache cleanup...");
        $this->info("Disk: {$diskName}");
        $this->info("Cache Path: {$cachePath}");
        $this->info("TTL: " . ($ttl / 60) . " minutes");

        if ($isDryRun) {
            $this->warn("DRY RUN MODE - No files will be deleted");
        }

        if ($isForce) {
            $this->warn("FORCE MODE - All cached PDFs will be deleted");
        }

        try {
            $disk = Storage::disk($diskName);
        } catch (\Throwable $exception) {
            $this->error("Failed to access disk '{$diskName}': " . $exception->getMessage());
            return Command::FAILURE;
        }

        if (! $disk->exists($cachePath)) {
            $this->info("Cache directory does not exist. Nothing to cleanup.");
            return Command::SUCCESS;
        }

        $files = $disk->files($cachePath);

        if (empty($files)) {
            $this->info("No cached PDF files found.");
            return Command::SUCCESS;
        }

        $this->info("Found " . count($files) . " cached PDF file(s)");

        $deletedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $this->newLine();

        foreach ($files as $file) {
            $fileName = basename($file);

            // Skip non-PDF files
            if (! str_ends_with($fileName, '.pdf')) {
                $this->line("Skipping non-PDF file: {$fileName}");
                $skippedCount++;
                continue;
            }

            try {
                $lastModified = $disk->lastModified($file);
                $age = time() - $lastModified;
                $ageInMinutes = round($age / 60, 2);

                if ($isForce) {
                    $shouldDelete = true;
                    $reason = "Force mode enabled";
                } else {
                    $shouldDelete = $age > $ttl;
                    $reason = $shouldDelete
                        ? "Expired (age: {$ageInMinutes} minutes)"
                        : "Still valid (age: {$ageInMinutes} minutes)";
                }

                if ($shouldDelete) {
                    if ($isDryRun) {
                        $this->line("Would delete: {$fileName} - {$reason}");
                    } else {
                        $disk->delete($file);
                        $this->line("Deleted: {$fileName} - {$reason}");

                        // Clean up cache metadata
                        $metadataKey = 'pdf_cache_metadata:' . md5($file);
                        Cache::forget($metadataKey);
                    }
                    $deletedCount++;
                } else {
                    $this->line("Keeping: {$fileName} - {$reason}");
                    $skippedCount++;
                }
            } catch (\Throwable $exception) {
                $this->error("Failed to process {$fileName}: " . $exception->getMessage());
                $errorCount++;
            }
        }

        $this->newLine();
        $this->info("Cleanup Summary:");
        $this->info("- Total files found: " . count($files));
        $this->info("- Files " . ($isDryRun ? "to be deleted" : "deleted") . ": {$deletedCount}");
        $this->info("- Files skipped: {$skippedCount}");

        if ($errorCount > 0) {
            $this->warn("- Files with errors: {$errorCount}");
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info("Run without --dry-run to actually delete files.");
        }

        return Command::SUCCESS;
    }
}
