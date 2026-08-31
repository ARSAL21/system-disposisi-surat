<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\IncomingLetterStatus;
use App\Exceptions\DocumentStorageConflict;
use App\Exceptions\DocumentVersionStateConflict;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\User;
use App\Services\DocumentStorageGuard;
use App\Services\DocumentVersionPositionAssignmentResolver;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateLetterDocumentVersion
{
    private const string DISK = 'letter-documents';

    private const string DIRECTORY = 'letter-documents';

    public function __construct(
        private readonly DocumentVersionPositionAssignmentResolver $positionAssignmentResolver,
        private readonly DocumentStorageGuard $storageGuard,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(
        User $actor,
        IncomingLetter $incomingLetter,
        UploadedFile $file,
        string $correctionReason,
    ): LetterDocument {
        $realPath = $file->getRealPath();
        $sha256 = is_string($realPath) ? hash_file('sha256', $realPath) : false;
        $sizeBytes = $file->getSize();

        if ($sha256 === false || $sizeBytes === false || $sizeBytes < 1) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        try {
            $path = Storage::disk(self::DISK)->putFileAs(
                self::DIRECTORY.'/'.$incomingLetter->getKey(),
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

        try {
            return DB::transaction(function () use (
                $actor,
                $incomingLetter,
                $file,
                $correctionReason,
                $sha256,
                $sizeBytes,
                $path,
            ): LetterDocument {
                $lockedLetter = IncomingLetter::query()
                    ->whereKey($incomingLetter->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedLetter->status !== IncomingLetterStatus::Registered) {
                    throw DocumentVersionStateConflict::expectedRegistered($lockedLetter->status);
                }

                $latestDocument = LetterDocument::query()
                    ->where('incoming_letter_id', $lockedLetter->getKey())
                    ->orderByDesc('version_number')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if (! $latestDocument instanceof LetterDocument) {
                    throw DocumentStorageConflict::invalidMetadata();
                }

                $this->storageGuard->validateOfficialLetterDocument($lockedLetter, $latestDocument);

                if (LetterDocument::query()
                    ->where('incoming_letter_id', $lockedLetter->getKey())
                    ->where('sha256', strtolower($sha256))
                    ->exists()) {
                    $this->throwDuplicateFingerprint();
                }

                $positionAssignment = $this->positionAssignmentResolver->lockCreatingAssignment($actor);

                $document = new LetterDocument;
                $document->incoming_letter_id = $lockedLetter->getKey();
                $document->source_submission_document_id = null;
                $document->version_number = $latestDocument->version_number + 1;
                $document->replaces_document_id = $latestDocument->getKey();
                $document->storage_disk = self::DISK;
                $document->storage_path = $path;
                $document->original_filename = $this->safeOriginalFilename($file);
                $document->mime_type = 'application/pdf';
                $document->size_bytes = (int) $sizeBytes;
                $document->sha256 = strtolower($sha256);
                $document->correction_reason = $correctionReason;
                $document->uploaded_by_user_id = $actor->getKey();
                $document->save();

                $this->storageGuard->validateOfficialLetterDocument($lockedLetter, $document);

                $this->recordAudit->execute(
                    actor: $actor,
                    action: AuditAction::DocumentVersionCreated,
                    subjectType: 'letter_document',
                    subjectId: $document->getKey(),
                    newValues: [
                        'incoming_letter_id' => $lockedLetter->getKey(),
                        'version_number' => $document->version_number,
                        'replaces_document_id' => $latestDocument->getKey(),
                        'sha256' => $document->sha256,
                        'size_bytes' => $document->size_bytes,
                        'correction_reason' => $document->correction_reason,
                    ],
                    actorPositionAssignment: $positionAssignment,
                );

                return $document;
            }, attempts: 3);
        } catch (Throwable $exception) {
            try {
                Storage::disk(self::DISK)->delete($path);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            if ($exception instanceof QueryException && $this->isDuplicateFingerprintViolation($exception)) {
                $this->throwDuplicateFingerprint();
            }

            throw $exception;
        }
    }

    private function safeOriginalFilename(UploadedFile $file): string
    {
        $filename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?: 'document.pdf';

        return Str::substr($filename, 0, 255);
    }

    private function isDuplicateFingerprintViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'letter_documents_incoming_letter_id_sha256_unique')
            || str_contains($message, 'letter_documents.incoming_letter_id, letter_documents.sha256');
    }

    private function throwDuplicateFingerprint(): never
    {
        throw ValidationException::withMessages([
            'document' => 'Berkas identik dengan salah satu versi dokumen yang sudah tersimpan.',
        ]);
    }
}
