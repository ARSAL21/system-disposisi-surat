<?php

namespace App\Services;

use App\Exceptions\DocumentStorageConflict;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\Models\StandaloneOutgoingDraft;
use App\StandaloneOutgoing\StoredStandaloneOutgoingDocument;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class StandaloneOutgoingDocumentStorage
{
    public const string DISK = 'standalone-outgoing-documents';

    public function store(UploadedFile $file, string $draftPublicId): StoredStandaloneOutgoingDocument
    {
        $realPath = $file->getRealPath();
        $hash = is_string($realPath) ? hash_file('sha256', $realPath) : false;
        $size = $file->getSize();

        if ($hash === false || $size === false || $size < 1) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        try {
            $path = Storage::disk(self::DISK)->putFileAs(
                'drafts/'.$draftPublicId,
                $file,
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
            originalFilename: $this->safeFilename($file),
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

    public function copy(StandaloneOutgoingDocumentVersion $source, StandaloneOutgoingDraft $sourceDraft, string $targetDraftPublicId): StoredStandaloneOutgoingDocument
    {
        $this->validate($sourceDraft, $source);

        try {
            $from = Storage::disk(self::DISK)->readStream($source->storage_path);
            if (! is_resource($from)) {
                throw DocumentStorageConflict::unavailable();
            }

            $temporaryPath = tempnam(sys_get_temp_dir(), 'outgoing-correction-');
            if ($temporaryPath === false) {
                fclose($from);
                throw DocumentStorageConflict::unavailable();
            }

            try {
                $to = fopen($temporaryPath, 'wb');
                if (! is_resource($to)) {
                    throw DocumentStorageConflict::unavailable();
                }
                stream_copy_to_stream($from, $to);
                fclose($to);
            } finally {
                fclose($from);
            }

            $hash = hash_file('sha256', $temporaryPath);
            $size = filesize($temporaryPath);
            if ($hash === false || $size === false || $size < 1) {
                throw DocumentStorageConflict::invalidMetadata();
            }
            $path = Storage::disk(self::DISK)->putFileAs(
                'drafts/'.$targetDraftPublicId,
                new File($temporaryPath),
                Str::uuid()->toString().'.pdf',
            );
            if ($path === false) {
                throw DocumentStorageConflict::unavailable();
            }

            return new StoredStandaloneOutgoingDocument(
                disk: self::DISK,
                path: $path,
                originalFilename: $source->original_filename,
                mimeType: 'application/pdf',
                sizeBytes: (int) $size,
                sha256: strtolower($hash),
            );
        } catch (DocumentStorageConflict $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            throw DocumentStorageConflict::unavailable();
        } finally {
            if (isset($temporaryPath) && is_string($temporaryPath) && is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    public function validate(StandaloneOutgoingDraft $draft, StandaloneOutgoingDocumentVersion $version): void
    {
        if ((int) $version->standalone_outgoing_draft_id !== (int) $draft->getKey()
            || $version->storage_disk !== self::DISK) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $guard = new DocumentStorageGuard;
        $guard->validatePath($version->storage_path, 'drafts/'.$draft->public_id.'/');
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
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?: 'konsep-surat.pdf';

        return Str::substr($filename, 0, 255);
    }
}
