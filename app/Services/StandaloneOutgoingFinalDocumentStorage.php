<?php

namespace App\Services;

use App\Exceptions\DocumentStorageConflict;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDocumentVersion;
use App\StandaloneOutgoing\StoredStandaloneOutgoingDocument;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class StandaloneOutgoingFinalDocumentStorage
{
    public const string DISK = 'standalone-outgoing-final-documents';

    public function store(UploadedFile $file, OutgoingLetter $letter): StoredStandaloneOutgoingDocument
    {
        $realPath = $file->getRealPath();

        return $this->storePath($realPath, $this->safeFilename($file), $letter);
    }

    public function storePath(string|false $sourcePath, string $filename, OutgoingLetter $letter): StoredStandaloneOutgoingDocument
    {
        if ($sourcePath === false || ! is_file($sourcePath) || $letter->origin->value !== 'STANDALONE') {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $hash = hash_file('sha256', $sourcePath);
        $size = filesize($sourcePath);
        if ($hash === false || $size === false || $size < 1) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        try {
            $path = Storage::disk(self::DISK)->putFileAs(
                'letters/'.$letter->public_id,
                new File($sourcePath),
                Str::uuid()->toString().'.pdf',
            );
        } catch (Throwable $exception) {
            report($exception);
            throw DocumentStorageConflict::unavailable();
        }

        if ($path === false) {
            throw DocumentStorageConflict::unavailable();
        }

        return new StoredStandaloneOutgoingDocument(
            disk: self::DISK,
            path: $path,
            originalFilename: $filename,
            mimeType: 'application/pdf',
            sizeBytes: (int) $size,
            sha256: strtolower($hash),
        );
    }

    public function delete(StoredStandaloneOutgoingDocument $document): void
    {
        try {
            Storage::disk($document->disk)->delete($document->path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function validate(OutgoingLetter $letter, OutgoingLetterDocumentVersion $version): void
    {
        if ($letter->origin->value !== 'STANDALONE'
            || (int) $version->outgoing_letter_id !== (int) $letter->getKey()
            || $version->storage_disk !== self::DISK) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $guard = new DocumentStorageGuard;
        $guard->validatePath($version->storage_path, 'letters/'.$letter->public_id.'/');
        $guard->validateMimeType($version->mime_type);
        $guard->validateMetadata($version->sha256, $version->size_bytes);
        $this->validateStoredFile($version->storage_path, $version->size_bytes);
    }

    private function validateStoredFile(string $path, int $expectedSize): void
    {
        try {
            $disk = Storage::disk(self::DISK);
            if (! $disk->exists($path)) {
                throw DocumentStorageConflict::unavailable();
            }

            if ($disk->size($path) !== $expectedSize) {
                throw DocumentStorageConflict::invalidMetadata();
            }
        } catch (DocumentStorageConflict $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            throw DocumentStorageConflict::unavailable();
        }
    }

    private function safeFilename(UploadedFile $file): string
    {
        $filename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?: 'surat-bertandatangan.pdf';

        return Str::substr($filename, 0, 255);
    }
}
