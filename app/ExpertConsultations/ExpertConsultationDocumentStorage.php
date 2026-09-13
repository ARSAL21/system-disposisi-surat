<?php

namespace App\ExpertConsultations;

use App\Exceptions\DocumentStorageConflict;
use App\Models\ExpertConsultation;
use App\Models\ExpertConsultationDocument;
use App\Services\DocumentStorageGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class ExpertConsultationDocumentStorage
{
    public const string DISK = 'expert-consultation-documents';

    public function store(UploadedFile $file, int $consultationId): StoredExpertConsultationDocument
    {
        $realPath = $file->getRealPath();
        $hash = is_string($realPath) ? hash_file('sha256', $realPath) : false;
        $size = $file->getSize();
        if ($hash === false || ! is_int($size) || $size < 1) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        try {
            $path = Storage::disk(self::DISK)->putFileAs(
                'expert-consultations/'.$consultationId,
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

        return new StoredExpertConsultationDocument(
            disk: self::DISK,
            path: $path,
            originalFilename: $this->safeFilename($file),
            mimeType: 'application/pdf',
            sizeBytes: $size,
            sha256: strtolower($hash),
        );
    }

    public function delete(StoredExpertConsultationDocument $document): void
    {
        try {
            Storage::disk($document->disk)->delete($document->path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function validate(ExpertConsultation $consultation, ExpertConsultationDocument $document): void
    {
        if ((int) $document->expert_consultation_id !== (int) $consultation->getKey() || $document->storage_disk !== self::DISK) {
            throw DocumentStorageConflict::invalidMetadata();
        }
        $guard = new DocumentStorageGuard;
        $guard->validatePath($document->storage_path, 'expert-consultations/'.$consultation->getKey().'/');
        $guard->validateMimeType($document->mime_type);
        $guard->validateMetadata($document->sha256, $document->size_bytes);
    }

    private function safeFilename(UploadedFile $file): string
    {
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?: 'telaah.pdf';

        return Str::substr($name, 0, 255);
    }
}
