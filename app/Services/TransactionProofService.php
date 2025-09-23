<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TransactionProofService
{
    public function prepareForStore(?UploadedFile $file, ?string $customName, Carbon $date, string $type): array
    {
        if (!$file) {
            return [];
        }

        return $this->storeFile($file, $customName, $date, $type);
    }

    public function handleUpdate(Transaction $transaction, ?UploadedFile $file, ?string $customName, Carbon $date, string $type): array
    {
        if ($file) {
            $this->deleteExisting($transaction);

            return $this->storeFile($file, $customName, $date, $type);
        }

        if (!$transaction->proof_path) {
            return [];
        }

        $target = $this->determineTarget($transaction, $customName, $date, $type);

        if ($target['path'] === $transaction->proof_path && $target['directory'] === $transaction->proof_directory && $target['filename'] === $transaction->proof_filename) {
            return [];
        }

        $this->moveExisting($transaction, $target);

        return [
            'proof_disk' => $target['disk'],
            'proof_directory' => $target['directory'],
            'proof_path' => $target['path'],
            'proof_filename' => $target['filename'],
            'proof_original_name' => $transaction->proof_original_name,
        ];
    }

    protected function storeFile(UploadedFile $file, ?string $customName, Carbon $date, string $type): array
    {
        $storageMode = $this->storageMode();
        $baseDirectory = $this->baseDirectory($storageMode);
        $relativeDirectory = $this->buildRelativeDirectory($date, $type);
        $filename = $this->generateFilename($file->getClientOriginalExtension(), $customName, $file->getClientOriginalName());
        $relativePath = ltrim($relativeDirectory.'/'.$filename, '/');

        if ($storageMode === 'drive') {
            $directory = rtrim($baseDirectory, '/\\');
            $absoluteDirectory = $this->joinPath($directory ?: storage_path('app/transaction-drive'), $relativeDirectory);
            File::ensureDirectoryExists($absoluteDirectory);
            $file->move($absoluteDirectory, $filename);
            $disk = 'drive';
        } else {
            $disk = 'public';
            $directory = trim($baseDirectory, '/');
            $fullDirectory = $directory ? $directory.'/'.$relativeDirectory : $relativeDirectory;
            Storage::disk($disk)->putFileAs($fullDirectory, $file, $filename);
        }

        return [
            'proof_disk' => $disk,
            'proof_directory' => isset($directory) && $directory !== '' ? $directory : null,
            'proof_path' => $relativePath,
            'proof_filename' => $filename,
            'proof_original_name' => $file->getClientOriginalName(),
        ];
    }

    protected function deleteExisting(Transaction $transaction): void
    {
        if (!$transaction->proof_path) {
            return;
        }

        $fullPath = $this->absolutePath($transaction->proof_directory, $transaction->proof_path, $transaction->proof_disk);

        if (!$fullPath) {
            return;
        }

        if ($transaction->proof_disk === 'drive') {
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }

            return;
        }

        $disk = $transaction->proof_disk ?: 'public';

        if (in_array($disk, array_keys(config('filesystems.disks', [])), true)) {
            Storage::disk($disk)->delete($this->normalisePath($transaction->proof_directory, $transaction->proof_path));
        }
    }

    protected function determineTarget(Transaction $transaction, ?string $customName, Carbon $date, string $type): array
    {
        $storageMode = $this->storageMode();
        $directory = $transaction->proof_directory;

        if ($transaction->proof_disk === 'drive') {
            $directory = $directory ?: rtrim($this->baseDirectory('drive'), '/\\');
        } elseif ($transaction->proof_disk === 'public' || !$transaction->proof_disk) {
            $directory = $directory ?: trim($this->baseDirectory('server'), '/');
        }

        $extension = pathinfo($transaction->proof_filename, PATHINFO_EXTENSION) ?: 'jpg';
        $filename = $this->generateFilename($extension, $customName, $transaction->proof_filename, false);
        $relativeDirectory = $this->buildRelativeDirectory($date, $type);
        $relativePath = ltrim($relativeDirectory.'/'.$filename, '/');

        $disk = $transaction->proof_disk ?: ($storageMode === 'drive' ? 'drive' : 'public');

        return [
            'disk' => $disk,
            'directory' => $directory ?: null,
            'path' => $relativePath,
            'filename' => $filename,
        ];
    }

    protected function moveExisting(Transaction $transaction, array $target): void
    {
        $currentPath = $this->absolutePath($transaction->proof_directory, $transaction->proof_path, $transaction->proof_disk);
        $newPath = $this->absolutePath($target['directory'], $target['path'], $target['disk']);

        if (!$currentPath || !$newPath || $currentPath === $newPath) {
            return;
        }

        File::ensureDirectoryExists(dirname($newPath));
        File::move($currentPath, $newPath);
    }

    protected function absolutePath(?string $directory, ?string $relativePath, ?string $disk): ?string
    {
        if (!$relativePath) {
            return null;
        }

        $relative = $this->normalisePath($directory, $relativePath);

        if ($disk === 'drive') {
            $base = $directory ?: rtrim($this->baseDirectory('drive'), '/\\');

            return $this->joinPath($base ?: storage_path('app/transaction-drive'), $relativePath);
        }

        $disk = $disk ?: 'public';

        if (!in_array($disk, array_keys(config('filesystems.disks', [])), true)) {
            return null;
        }

        return Storage::disk($disk)->path($relative);
    }

    protected function baseDirectory(string $mode): string
    {
        if ($mode === 'drive') {
            $driveDirectory = Setting::get('transaction_proof_drive_directory');

            return $driveDirectory !== null && $driveDirectory !== ''
                ? $driveDirectory
                : storage_path('app/transaction-drive');
        }

        $serverDirectory = Setting::get('transaction_proof_server_directory');

        return $serverDirectory !== null && $serverDirectory !== ''
            ? $serverDirectory
            : 'transaction-proofs';
    }

    protected function storageMode(): string
    {
        return Setting::get('transaction_proof_storage', 'server') === 'drive' ? 'drive' : 'server';
    }

    protected function buildRelativeDirectory(Carbon $date, string $type): string
    {
        $sanitisedType = Str::slug($type, '-');

        if ($sanitisedType === '') {
            $sanitisedType = 'lainnya';
        }

        return $date->format('Y').'/'.$date->format('m').'/'.$sanitisedType;
    }

    protected function generateFilename(string $extension, ?string $customName, string $fallbackName, bool $allowRandom = true): string
    {
        $name = $customName ? Str::slug($customName, '-') : Str::slug(pathinfo($fallbackName, PATHINFO_FILENAME), '-');

        if (!$name && $allowRandom) {
            $name = (string) Str::uuid();
        }

        if (!$name) {
            $name = 'bukti-transaksi';
        }

        $extension = ltrim($extension, '.');

        return strtolower($name.'.'.($extension ?: 'jpg'));
    }

    protected function normalisePath(?string $directory, string $relativePath): string
    {
        $path = ltrim($relativePath, '/');
        $directory = trim((string) $directory, '/');

        return $directory ? $directory.'/'.$path : $path;
    }

    protected function joinPath(string $base, string $relative): string
    {
        $normalisedBase = rtrim($base, '/\\');
        $normalisedRelative = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, ltrim($relative, '\/'));

        return ($normalisedBase !== '' ? $normalisedBase.DIRECTORY_SEPARATOR : '').$normalisedRelative;
    }
}
