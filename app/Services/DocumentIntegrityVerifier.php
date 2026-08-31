<?php

namespace App\Services;

use App\Exceptions\DocumentIntegrityConflict;
use App\Models\LetterDocument;
use App\Models\LetterSubmission;
use App\Models\SubmissionDocument;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentIntegrityVerifier
{
    public function __construct(
        private readonly DocumentStorageGuard $storageGuard = new DocumentStorageGuard,
    ) {}

    /**
     * Inspect and diagnose the cryptographic integrity of a document without throwing exceptions.
     */
    public function inspect(SubmissionDocument|LetterDocument $document): DocumentIntegrityResult
    {
        $allowedDisks = $document instanceof SubmissionDocument
            ? DocumentStorageGuard::ALLOWED_SUBMISSION_DISKS
            : DocumentStorageGuard::ALLOWED_LETTER_DISKS;

        $disk = $document->storage_disk;
        if (! in_array($disk, $allowedDisks, true)) {
            return DocumentIntegrityResult::invalidDisk();
        }

        $path = $document->storage_path;
        $expectedPrefix = null;
        if ($document instanceof SubmissionDocument) {
            $submission = $document->relationLoaded('submission')
                ? $document->getRelation('submission')
                : $document->submission()->first();

            if (! $submission instanceof LetterSubmission || empty($submission->public_id)) {
                return DocumentIntegrityResult::invalidMetadata('Associated letter submission record is missing or invalid.');
            }

            $expectedPrefix = $submission->public_id.'/';
        }

        try {
            $this->storageGuard->validatePath($path, $expectedPrefix);
        } catch (Throwable) {
            return DocumentIntegrityResult::invalidPath();
        }

        if (! $this->storageGuard->isValidSha256($document->sha256)
            || ! $this->storageGuard->isValidSizeBytes($document->size_bytes)
            || strtolower(trim((string) $document->mime_type)) !== 'application/pdf'
        ) {
            return DocumentIntegrityResult::invalidMetadata();
        }

        try {
            $diskStorage = Storage::disk($disk);
            if (! $diskStorage->exists($path)) {
                return DocumentIntegrityResult::unavailable();
            }

            $stream = $diskStorage->readStream($path);
        } catch (Throwable) {
            return DocumentIntegrityResult::unavailable();
        }

        if (! is_resource($stream)) {
            return DocumentIntegrityResult::unavailable();
        }

        try {
            $context = hash_init('sha256');
            $bytesRead = hash_update_stream($context, $stream);
            $actualHash = strtolower(hash_final($context));
        } finally {
            fclose($stream);
        }

        $expectedHash = strtolower($document->sha256);
        $expectedBytes = (int) $document->size_bytes;

        if ($bytesRead !== $expectedBytes) {
            return DocumentIntegrityResult::sizeMismatch(
                expectedHash: $expectedHash,
                actualHash: $actualHash,
                expectedBytes: $expectedBytes,
                actualBytes: $bytesRead,
            );
        }

        if (! hash_equals($expectedHash, $actualHash)) {
            return DocumentIntegrityResult::hashMismatch(
                expectedHash: $expectedHash,
                actualHash: $actualHash,
                expectedBytes: $expectedBytes,
                actualBytes: $bytesRead,
            );
        }

        return DocumentIntegrityResult::match(
            hash: $actualHash,
            bytes: $bytesRead,
        );
    }

    /**
     * Verify document integrity and fail-securely throw DocumentIntegrityConflict on any mismatch.
     *
     * @throws DocumentIntegrityConflict
     */
    public function verifyOrFail(SubmissionDocument|LetterDocument $document): DocumentIntegrityResult
    {
        $result = $this->inspect($document);

        return match ($result->status) {
            DocumentIntegrityResult::STATUS_MATCH => $result,
            DocumentIntegrityResult::STATUS_INVALID_DISK => throw DocumentIntegrityConflict::invalidDisk(),
            DocumentIntegrityResult::STATUS_INVALID_PATH => throw DocumentIntegrityConflict::invalidPath(),
            DocumentIntegrityResult::STATUS_INVALID_METADATA => throw DocumentIntegrityConflict::invalidMetadata(),
            DocumentIntegrityResult::STATUS_FILE_UNAVAILABLE => throw DocumentIntegrityConflict::unavailable(),
            DocumentIntegrityResult::STATUS_HASH_MISMATCH,
            DocumentIntegrityResult::STATUS_SIZE_MISMATCH => throw DocumentIntegrityConflict::fingerprintMismatch(),
            default => throw DocumentIntegrityConflict::fingerprintMismatch(),
        };
    }
}
