<?php

namespace App\Services;

use App\Exceptions\DocumentStorageConflict;
use App\LetterResponses\StoredLetterResponseDocument;
use App\Models\LetterResponseDocumentVersion;
use App\Models\LetterResponseDossier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class LetterResponseDocumentStorage
{
    public const string DISK = 'letter-response-documents';

    public function store(UploadedFile $file, int $incomingLetterId): StoredLetterResponseDocument
    {
        $realPath = $file->getRealPath();
        $hash = is_string($realPath) ? hash_file('sha256', $realPath) : false;
        $size = $file->getSize();

        if ($hash === false || $size === false || $size < 1) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $directory = 'letters/'.$incomingLetterId;

        try {
            $path = Storage::disk(self::DISK)->putFileAs($directory, $file, Str::uuid()->toString().'.pdf');
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
            originalFilename: $this->safeOriginalFilename($file),
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

    public function validate(LetterResponseDossier $dossier, LetterResponseDocumentVersion $version): void
    {
        $document = $version->relationLoaded('document')
            ? $version->document
            : $version->document()->first();

        if ($document === null
            || (int) $document->letter_response_dossier_id !== (int) $dossier->getKey()
            || $version->storage_disk !== self::DISK) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        (new DocumentStorageGuard)->validatePath(
            $version->storage_path,
            'letters/'.$dossier->incoming_letter_id.'/',
        );
        (new DocumentStorageGuard)->validateMimeType($version->mime_type);
        (new DocumentStorageGuard)->validateMetadata($version->sha256, $version->size_bytes);
    }

    private function safeOriginalFilename(UploadedFile $file): string
    {
        $filename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?: 'document.pdf';

        return Str::substr($filename, 0, 255);
    }
}
