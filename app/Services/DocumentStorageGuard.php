<?php

namespace App\Services;

use App\Exceptions\DocumentStorageConflict;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\LetterSubmission;
use App\Models\SubmissionDocument;

class DocumentStorageGuard
{
    public const ALLOWED_SUBMISSION_DISKS = [
        'submission-documents',
    ];

    public const ALLOWED_LETTER_DISKS = [
        'letter-documents',
        'submission-documents',
    ];

    /**
     * Validate all storage invariants for a SubmissionDocument.
     *
     * @throws DocumentStorageConflict
     */
    public function validateSubmissionDocument(SubmissionDocument $document, ?LetterSubmission $submission = null): void
    {
        $this->validateDisk($document->storage_disk, self::ALLOWED_SUBMISSION_DISKS);

        $relatedSubmission = $document->relationLoaded('submission')
            ? $document->getRelation('submission')
            : null;
        $expectedPrefix = $submission instanceof LetterSubmission
            ? $submission->public_id.'/'
            : ($relatedSubmission instanceof LetterSubmission
                ? $relatedSubmission->public_id.'/'
                : null);

        $this->validatePath($document->storage_path, $expectedPrefix);
        $this->validateMimeType($document->mime_type);
        $this->validateMetadata($document->sha256, $document->size_bytes);
    }

    /**
     * Validate all storage invariants for an official LetterDocument.
     *
     * @throws DocumentStorageConflict
     */
    public function validateLetterDocument(LetterDocument $document): void
    {
        $this->validateDisk($document->storage_disk, self::ALLOWED_LETTER_DISKS);
        $this->validatePath($document->storage_path);
        $this->validateMimeType($document->mime_type);
        $this->validateMetadata($document->sha256, $document->size_bytes);
    }

    /**
     * Validate the complete relational and storage boundary for an official
     * document version before it is streamed or accepted as the current base.
     *
     * @throws DocumentStorageConflict
     */
    public function validateOfficialLetterDocument(
        IncomingLetter $incomingLetter,
        LetterDocument $document,
    ): void {
        if ((int) $document->incoming_letter_id !== (int) $incomingLetter->getKey()) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $this->validateMimeType($document->mime_type);
        $this->validateMetadata($document->sha256, $document->size_bytes);

        if ($document->source_submission_document_id !== null) {
            $this->validateInitialLetterDocument($incomingLetter, $document);

            return;
        }

        $this->validateCorrectionLetterDocument($incomingLetter, $document);
    }

    /** @throws DocumentStorageConflict */
    private function validateInitialLetterDocument(
        IncomingLetter $incomingLetter,
        LetterDocument $document,
    ): void {
        if ($document->version_number !== 1 || $document->replaces_document_id !== null) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $source = $document->relationLoaded('sourceSubmissionDocument')
            ? $document->sourceSubmissionDocument
            : $document->sourceSubmissionDocument()->first();

        if (! $source instanceof SubmissionDocument
            || (int) $source->letter_submission_id !== (int) $incomingLetter->letter_submission_id
            || $document->storage_disk !== $source->storage_disk
            || $document->storage_path !== $source->storage_path
            || strtolower($document->mime_type) !== strtolower($source->mime_type)
            || (int) $document->size_bytes !== (int) $source->size_bytes
            || ! hash_equals(strtolower($document->sha256), strtolower($source->sha256))
        ) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $submission = $source->relationLoaded('submission')
            ? $source->submission
            : $source->submission()->first();

        if (! $submission instanceof LetterSubmission) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $this->validateDisk($document->storage_disk, self::ALLOWED_SUBMISSION_DISKS);
        $this->validatePath($document->storage_path, $submission->public_id.'/');
    }

    /** @throws DocumentStorageConflict */
    private function validateCorrectionLetterDocument(
        IncomingLetter $incomingLetter,
        LetterDocument $document,
    ): void {
        if ($document->version_number < 2 || $document->replaces_document_id === null) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $replaced = $document->relationLoaded('replacesDocument')
            ? $document->replacesDocument
            : $document->replacesDocument()->first();

        if (! $replaced instanceof LetterDocument
            || (int) $replaced->incoming_letter_id !== (int) $incomingLetter->getKey()
            || $replaced->version_number !== $document->version_number - 1
        ) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $this->validateDisk($document->storage_disk, ['letter-documents']);
        $this->validatePath(
            $document->storage_path,
            'letter-documents/'.$incomingLetter->getKey().'/',
        );
    }

    /**
     * Validate disk against an allowed disk list.
     *
     * @param  array<int, string>  $allowedDisks
     *
     * @throws DocumentStorageConflict
     */
    public function validateDisk(?string $disk, array $allowedDisks): void
    {
        if ($disk === null || ! in_array($disk, $allowedDisks, true)) {
            throw DocumentStorageConflict::invalidDisk();
        }
    }

    /**
     * Validate storage path against directory traversal, null bytes, absolute paths, extension, and optional prefix.
     *
     * @throws DocumentStorageConflict
     */
    public function validatePath(?string $path, ?string $expectedPrefix = null): void
    {
        if ($path === null || trim($path) === '') {
            throw DocumentStorageConflict::invalidPath();
        }

        if (str_contains($path, "\0")
            || str_contains($path, '..')
            || str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[a-zA-Z]:/', $path)
        ) {
            throw DocumentStorageConflict::invalidPath();
        }

        if (! str_ends_with(strtolower($path), '.pdf')) {
            throw DocumentStorageConflict::invalidPath();
        }

        if ($expectedPrefix !== null && ! str_starts_with($path, $expectedPrefix)) {
            throw DocumentStorageConflict::invalidPath();
        }
    }

    /**
     * Validate MIME type strictly matches application/pdf.
     *
     * @throws DocumentStorageConflict
     */
    public function validateMimeType(?string $mimeType): void
    {
        if ($mimeType === null || strtolower(trim($mimeType)) !== 'application/pdf') {
            throw DocumentStorageConflict::invalidMime();
        }
    }

    /**
     * Validate hash format and size in bytes.
     *
     * @throws DocumentStorageConflict
     */
    public function validateMetadata(?string $sha256, mixed $sizeBytes): void
    {
        if ($sha256 === null || ! preg_match('/^[a-f0-9]{64}$/i', $sha256)) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        if (! is_numeric($sizeBytes) || (int) $sizeBytes < 0) {
            throw DocumentStorageConflict::invalidMetadata();
        }
    }

    public function isValidSha256(?string $sha256): bool
    {
        return $sha256 !== null && preg_match('/^[a-f0-9]{64}$/i', $sha256) === 1;
    }

    public function isValidSizeBytes(mixed $sizeBytes): bool
    {
        return is_numeric($sizeBytes) && (int) $sizeBytes >= 0;
    }
}
