<?php

namespace App\Services;

use App\Exceptions\DocumentStorageConflict;
use App\LetterResponses\StoredLetterResponseDocument;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDocumentVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class OutgoingLetterDocumentStorage
{
    public const string DISK = 'outgoing-letter-documents';

    public function store(UploadedFile $file, OutgoingLetter $letter): StoredLetterResponseDocument
    {
        $realPath = $file->getRealPath();
        $hash = is_string($realPath) ? hash_file('sha256', $realPath) : false;
        $size = $file->getSize();

        if ($hash === false || $size === false || $size < 1 || $letter->incoming_letter_id === null) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $directory = 'letters/'.$letter->incoming_letter_id.'/mandates/'.$letter->getKey();

        try {
            $path = Storage::disk(self::DISK)->putFileAs(
                $directory,
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

        return new StoredLetterResponseDocument(
            disk: self::DISK,
            path: $path,
            originalFilename: $this->safeFilename($file),
            mimeType: 'application/pdf',
            sizeBytes: (int) $size,
            sha256: strtolower($hash),
        );
    }

    public function delete(StoredLetterResponseDocument $document): void
    {
        try {
            Storage::disk($document->disk)->delete($document->path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function validate(OutgoingLetter $letter, OutgoingLetterDocumentVersion $version): void
    {
        if ((int) $version->outgoing_letter_id !== (int) $letter->getKey()
            || $letter->incoming_letter_id === null
            || $version->storage_disk !== self::DISK) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $guard = new DocumentStorageGuard;
        $guard->validatePath(
            $version->storage_path,
            'letters/'.$letter->incoming_letter_id.'/mandates/'.$letter->getKey().'/',
        );
        $guard->validateMimeType($version->mime_type);
        $guard->validateMetadata($version->sha256, $version->size_bytes);
    }

    private function safeFilename(UploadedFile $file): string
    {
        $filename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?: 'surat-keluar.pdf';

        return Str::substr($filename, 0, 255);
    }
}
