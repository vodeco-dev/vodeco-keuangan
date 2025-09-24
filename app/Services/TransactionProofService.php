<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

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
            'proof_remote_id' => $transaction->proof_remote_id,
        ];
    }

    protected function storeFile(UploadedFile $file, ?string $customName, Carbon $date, string $type): array
    {
        $storageMode = $this->storageMode();
        $relativeDirectory = $this->buildRelativeDirectory($date, $type);
        $filename = $this->generateFilename(
            $file->getClientOriginalExtension(),
            $customName,
            $file->getClientOriginalName()
        );
        $relativePath = ltrim($relativeDirectory.'/'.$filename, '/');

        if ($storageMode === 'drive') {
            return $this->storeFileOnDrive($file, $relativeDirectory, $filename, $relativePath);
        }

        $disk = 'public';
        $directory = trim($this->baseDirectory('server'), '/');
        $fullDirectory = $directory ? $directory.'/'.$relativeDirectory : $relativeDirectory;
        Storage::disk($disk)->putFileAs($fullDirectory, $file, $filename);

        return [
            'proof_disk' => $disk,
            'proof_directory' => $directory !== '' ? $directory : null,
            'proof_path' => $relativePath,
            'proof_filename' => $filename,
            'proof_original_name' => $file->getClientOriginalName(),
            'proof_remote_id' => null,
        ];
    }

    protected function storeFileOnDrive(UploadedFile $file, string $relativeDirectory, string $filename, string $relativePath): array
    {
        $folderId = $this->driveFolderId();

        if (!$folderId) {
            throw ValidationException::withMessages([
                'proof' => 'ID folder Google Drive belum dikonfigurasi. Mohon perbarui pengaturan penyimpanan.',
            ]);
        }

        try {
            Log::info('Attempting to upload proof to Google Drive.', ['folder_id' => $folderId, 'directory' => $relativeDirectory, 'filename' => $filename]);
            $result = $this->googleDriveService->upload($folderId, $relativeDirectory, $filename, $file);
            Log::info('Successfully uploaded proof to Google Drive.', ['result' => $result]);
        } catch (Throwable $e) {
            Log::error('Failed to upload proof to Google Drive.', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            report($e);

            throw ValidationException::withMessages([
                'proof' => 'Gagal mengunggah bukti transaksi ke Google Drive. Pesan error: '.$e->getMessage(),
            ]);
        }

        return [
            'proof_disk' => 'drive',
            'proof_directory' => $folderId,
            'proof_path' => $relativePath,
            'proof_filename' => $filename,
            'proof_original_name' => $file->getClientOriginalName(),
            'proof_remote_id' => $result['id'] ?? null,
        ];
    }

    protected function deleteExisting(Transaction $transaction): void
    {
        if (!$transaction->proof_path) {
            return;
        }

        if ($transaction->proof_disk === 'drive') {
            if ($transaction->proof_remote_id) {
                try {
                    $this->googleDriveService->delete($transaction->proof_remote_id);
                } catch (Throwable $e) {
                    report($e);
                }

                return;
            }

            $legacyPath = $this->absolutePath($transaction->proof_directory, $transaction->proof_path, 'drive');

            if ($legacyPath && File::exists($legacyPath)) {
                File::delete($legacyPath);
            }

            return;
        }

        $fullPath = $this->absolutePath($transaction->proof_directory, $transaction->proof_path, $transaction->proof_disk);

        if (!$fullPath) {
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
            if ($transaction->proof_remote_id) {
                $directory = $directory ?: $this->driveFolderId();
            } else {
                $directory = $directory ?: $this->legacyDriveDirectory();
            }
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
        if ($transaction->proof_disk === 'drive' && $transaction->proof_remote_id) {
            $baseFolderId = $target['directory'] ?: $this->driveFolderId() ?: $transaction->proof_directory;

            if (!$baseFolderId) {
                throw ValidationException::withMessages([
                    'proof' => 'ID folder Google Drive tidak tersedia. Mohon perbarui pengaturan penyimpanan.',
                ]);
            }

            $relativeDirectory = $this->extractRelativeDirectory($target['path']);

            try {
                $this->googleDriveService->move(
                    $transaction->proof_remote_id,
                    $baseFolderId,
                    $relativeDirectory,
                    $target['filename']
                );
            } catch (Throwable $e) {
                report($e);

                throw ValidationException::withMessages([
                    'proof' => 'Gagal memperbarui bukti transaksi di Google Drive. Silakan coba lagi atau hubungi administrator.',
                ]);
            }

            return;
        }

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

        if ($disk === 'drive') {
            if ($directory && $this->looksLikeLocalPath($directory)) {
                return $this->joinPath($directory, $relativePath);
            }

            $legacyDirectory = $this->legacyDriveDirectory();

            if ($legacyDirectory) {
                return $this->joinPath($legacyDirectory, $relativePath);
            }

            return null;
        }

        $disk = $disk ?: 'public';

        if (!in_array($disk, array_keys(config('filesystems.disks', [])), true)) {
            return null;
        }

        $relative = $this->normalisePath($directory, $relativePath);

        return Storage::disk($disk)->path($relative);
    }

    protected function baseDirectory(string $mode): string
    {
        if ($mode === 'drive') {
            return $this->legacyDriveDirectory() ?: storage_path('app/transaction-drive');
        }

        $serverDirectory = Setting::get('transaction_proof_server_directory');

        return $serverDirectory !== null && $serverDirectory !== ''
            ? $serverDirectory
            : 'transaction-proofs';
    }

    protected function driveFolderId(): ?string
    {
        $folderId = Setting::get('transaction_proof_drive_folder_id');

        if ($folderId) {
            return $folderId;
        }

        $legacy = Setting::get('transaction_proof_drive_directory');

        if ($legacy && !$this->looksLikeLocalPath($legacy)) {
            return $legacy;
        }

        return null;
    }

    protected function legacyDriveDirectory(): ?string
    {
        $legacy = Setting::get('transaction_proof_drive_directory');

        if ($legacy && $this->looksLikeLocalPath($legacy)) {
            return rtrim($legacy, '/\\');
        }

        return null;
    }

    protected function storageMode(): string
    {
        return 'server';
    }

    protected function extractRelativeDirectory(string $relativePath): string
    {
        $directory = dirname($relativePath);

        if ($directory === '.' || $directory === DIRECTORY_SEPARATOR) {
            return '';
        }

        return trim($directory, '/\\');
    }

    protected function looksLikeLocalPath(?string $value): bool
    {
        if (!$value) {
            return false;
        }

        return str_contains($value, '/') || str_contains($value, '\\');
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
