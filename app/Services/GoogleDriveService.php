<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use RuntimeException;

class GoogleDriveService
{
    protected ?Drive $drive = null;

    public function __construct(protected ?string $teamDriveId = null)
    {
        $this->teamDriveId = $this->teamDriveId ?: config('services.google_drive.team_drive_id');
    }

    public function upload(string $baseFolderId, string $relativeDirectory, string $filename, UploadedFile $file): array
    {
        $parentId = $this->ensurePath($baseFolderId, $relativeDirectory);
        $driveFile = new DriveFile([
            'name' => $filename,
            'parents' => [$parentId],
        ]);

        $fileContents = file_get_contents($file->getRealPath() ?: $file->getPathname());

        if ($fileContents === false) {
            throw new RuntimeException('Unable to read uploaded file content for Google Drive upload.');
        }

        $createdFile = $this->drive()->files->create(
            $driveFile,
            $this->mutationOptions([
                'data' => $fileContents,
                'mimeType' => $file->getMimeType() ?: 'application/octet-stream',
                'uploadType' => 'multipart',
                'fields' => 'id, name, parents',
            ])
        );

        return [
            'id' => $createdFile->getId(),
        ];
    }

    public function delete(string $fileId): void
    {
        $this->drive()->files->delete($fileId, $this->mutationOptions());
    }

    public function move(string $fileId, string $baseFolderId, string $relativeDirectory, string $filename): void
    {
        $drive = $this->drive();
        $targetParent = $this->ensurePath($baseFolderId, $relativeDirectory);

        $current = $drive->files->get($fileId, $this->mutationOptions([
            'fields' => 'id, parents',
        ]));

        $existingParents = Arr::wrap($current->getParents());
        $needsParentChange = $targetParent && !in_array($targetParent, $existingParents, true);

        $params = $this->mutationOptions([
            'fields' => 'id, name, parents',
        ]);

        if ($needsParentChange) {
            $params['addParents'] = $targetParent;

            if (!empty($existingParents)) {
                $params['removeParents'] = implode(',', $existingParents);
            }
        }

        $metadata = new DriveFile([
            'name' => $filename,
        ]);

        $drive->files->update($fileId, $metadata, $params);
    }

    protected function ensurePath(string $baseFolderId, string $relativeDirectory): string
    {
        $parentId = $baseFolderId;
        $segments = array_values(array_filter(array_map('trim', explode('/', $relativeDirectory))));

        foreach ($segments as $segment) {
            $parentId = $this->findOrCreateFolder($parentId, $segment);
        }

        return $parentId;
    }

    protected function findOrCreateFolder(string $parentId, string $name): string
    {
        $folderId = $this->findFolder($parentId, $name);

        if ($folderId) {
            return $folderId;
        }

        $folder = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);

        $created = $this->drive()->files->create($folder, $this->mutationOptions([
            'fields' => 'id, name',
        ]));

        return $created->getId();
    }

    protected function findFolder(string $parentId, string $name): ?string
    {
        $escapedName = str_replace("'", "\\'", $name);
        $query = sprintf(
            "mimeType = 'application/vnd.google-apps.folder' and name = '%s' and '%s' in parents and trashed = false",
            $escapedName,
            $parentId
        );

        $result = $this->drive()->files->listFiles($this->listOptions([
            'q' => $query,
            'pageSize' => 1,
            'fields' => 'files(id, name)',
        ]));

        $files = $result->getFiles();

        if (empty($files)) {
            return null;
        }

        return Arr::first($files)?->getId();
    }

    protected function drive(): Drive
    {
        if ($this->drive instanceof Drive) {
            return $this->drive;
        }

        $credentials = config('services.google_drive.credentials');

        if (!$credentials) {
            throw new RuntimeException('Google Drive credentials are not configured.');
        }

        $client = new Client();
        $client->setApplicationName(config('app.name', 'Laravel').' Google Drive');
        $client->setScopes([Drive::DRIVE_FILE]);
        $client->setAccessType('offline');

        if ($path = $this->resolveCredentialsPath($credentials)) {
            $client->setAuthConfig($path);
        } else {
            $decoded = json_decode($credentials, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                throw new RuntimeException('Invalid Google Drive service account credentials.');
            }

            $client->setAuthConfig($decoded);
        }

        $impersonate = config('services.google_drive.impersonate');

        if ($impersonate) {
            $client->setSubject($impersonate);
        }

        $this->drive = new Drive($client);

        return $this->drive;
    }

    protected function resolveCredentialsPath(string $credentials): ?string
    {
        $trimmed = trim($credentials);

        if ($trimmed === '') {
            return null;
        }

        $paths = [$trimmed];

        if (!str_starts_with($trimmed, DIRECTORY_SEPARATOR)) {
            $paths[] = base_path($trimmed);
            $paths[] = storage_path($trimmed);
        }

        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function listOptions(array $options = []): array
    {
        $options['supportsAllDrives'] = true;
        $options['includeItemsFromAllDrives'] = true;

        if ($this->teamDriveId) {
            $options['driveId'] = $this->teamDriveId;
            $options['corpora'] = 'drive';
        }

        return $options;
    }

    protected function mutationOptions(array $options = []): array
    {
        $options['supportsAllDrives'] = true;

        return $options;
    }
}

