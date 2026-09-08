<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\SubmissionReviewOutcome;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Exceptions\SubmissionStateConflict;
use App\Intake\SubmissionScreeningChecklist;
use App\Models\LetterSubmission;
use App\Models\SubmissionDocument;
use App\Models\SubmissionReview;
use App\Models\User;
use App\Services\IntakePositionAssignmentResolver;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class ResubmitManualSubmission
{
    private const string DISK = 'submission-documents';

    public function __construct(
        private readonly IntakePositionAssignmentResolver $positionAssignmentResolver,
        private readonly RecordAudit $recordAudit,
    ) {}

    /**
     * @param array{
     *     sender_organization_name: string,
     *     contact_name: string,
     *     contact_email: string|null,
     *     contact_phone: string|null,
     *     received_at: CarbonInterface,
     *     external_letter_number: string|null,
     *     external_letter_date: CarbonInterface|null,
     *     subject: string,
     *     summary: string|null,
     *     checklist: list<array{id: string, checked: bool}>,
     *     screening_note: string|null
     * } $attributes
     */
    public function execute(
        User $actor,
        LetterSubmission $submission,
        array $attributes,
        ?UploadedFile $file,
    ): LetterSubmission {
        $newFileData = $file instanceof UploadedFile
            ? $this->storeFile($submission->public_id, $file)
            : null;
        $oldPath = null;

        try {
            $result = DB::transaction(function () use ($actor, $submission, $attributes, $newFileData, &$oldPath): LetterSubmission {
                $lockedSubmission = LetterSubmission::query()
                    ->whereKey($submission->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedSubmission->source !== SubmissionSource::Manual) {
                    abort(404);
                }

                if ($lockedSubmission->status !== SubmissionStatus::InternalRevisionRequired) {
                    throw SubmissionStateConflict::expectedManualRevision($lockedSubmission->status);
                }

                $document = SubmissionDocument::query()
                    ->where('letter_submission_id', $lockedSubmission->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $document instanceof SubmissionDocument && $newFileData === null) {
                    throw ValidationException::withMessages([
                        'document' => 'Dokumen PDF wajib tersedia sebelum diajukan kembali.',
                    ]);
                }

                $positionAssignment = $this->positionAssignmentResolver->lockActiveAssignment($actor);
                $oldDocumentHash = $document?->sha256;
                $oldReceivedAt = $lockedSubmission->received_at?->toISOString();

                $lockedSubmission->sender_organization_name = $attributes['sender_organization_name'];
                $lockedSubmission->contact_name = $attributes['contact_name'];
                $lockedSubmission->contact_email = $attributes['contact_email'];
                $lockedSubmission->contact_phone = $attributes['contact_phone'];
                $lockedSubmission->external_letter_number = $attributes['external_letter_number'];
                $lockedSubmission->external_letter_date = $attributes['external_letter_date'];
                $lockedSubmission->subject = $attributes['subject'];
                $lockedSubmission->summary = $attributes['summary'];
                $lockedSubmission->received_at = $attributes['received_at'];
                $lockedSubmission->submitted_at = now();
                $lockedSubmission->status = SubmissionStatus::ReadyForApproval;
                $lockedSubmission->save();

                if ($newFileData !== null) {
                    if (! $document instanceof SubmissionDocument) {
                        $document = new SubmissionDocument;
                        $document->letter_submission_id = $lockedSubmission->getKey();
                    } else {
                        $oldPath = $document->storage_path;
                    }

                    $document->storage_disk = self::DISK;
                    $document->storage_path = $newFileData['path'];
                    $document->original_filename = $newFileData['original_filename'];
                    $document->mime_type = $newFileData['mime_type'];
                    $document->size_bytes = $newFileData['size_bytes'];
                    $document->sha256 = $newFileData['sha256'];
                    $document->uploaded_by_user_id = $actor->getKey();
                    $document->save();
                }

                $review = new SubmissionReview;
                $review->letter_submission_id = $lockedSubmission->getKey();
                $review->outcome = SubmissionReviewOutcome::ReadyForApproval;
                $review->checklist = SubmissionScreeningChecklist::normalize($attributes['checklist']);
                $review->note = $attributes['screening_note'];
                $review->created_by_user_id = $actor->getKey();
                $review->created_by_position_assignment_id = $positionAssignment->getKey();
                $review->save();

                $this->recordAudit->execute(
                    actor: $actor,
                    action: AuditAction::SubmissionReadyForApproval,
                    subjectType: 'letter_submission',
                    subjectId: $lockedSubmission->getKey(),
                    oldValues: [
                        'status' => SubmissionStatus::InternalRevisionRequired->value,
                        'received_at' => $oldReceivedAt,
                        'sha256' => $oldDocumentHash,
                    ],
                    newValues: [
                        'status' => SubmissionStatus::ReadyForApproval->value,
                        'received_at' => $lockedSubmission->received_at->toISOString(),
                        'sha256' => $document->sha256,
                    ],
                    metadata: [
                        'public_id' => $lockedSubmission->public_id,
                        'submission_review_id' => $review->getKey(),
                        'document_replaced' => $newFileData !== null,
                    ],
                    actorPositionAssignment: $positionAssignment,
                );

                return $lockedSubmission->load(['document', 'latestReview', 'latestDecision']);
            }, attempts: 3);
        } catch (Throwable $exception) {
            if ($newFileData !== null) {
                $this->deleteFile($newFileData['path']);
            }

            throw $exception;
        }

        if (is_string($oldPath) && $oldPath !== ($newFileData['path'] ?? null)) {
            $this->deleteFile($oldPath);
        }

        return $result;
    }

    /**
     * @return array{path: string, original_filename: string, mime_type: string, size_bytes: int, sha256: string}
     */
    private function storeFile(string $publicId, UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        $sha256 = is_string($realPath) ? hash_file('sha256', $realPath) : false;
        $size = $file->getSize();
        $mime = strtolower((string) $file->getMimeType());

        if ($sha256 === false || $size === false || $mime !== 'application/pdf') {
            throw new RuntimeException('Unable to read validated manual intake document metadata.');
        }

        $path = Storage::disk(self::DISK)->putFileAs(
            $publicId,
            $file,
            Str::uuid()->toString().'.pdf',
        );

        if ($path === false) {
            throw new RuntimeException('Unable to store the manual intake document.');
        }

        $filename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?: 'document.pdf';

        return [
            'path' => $path,
            'original_filename' => Str::substr($filename, 0, 255),
            'mime_type' => $mime,
            'size_bytes' => (int) $size,
            'sha256' => $sha256,
        ];
    }

    private function deleteFile(string $path): void
    {
        try {
            Storage::disk(self::DISK)->delete($path);
        } catch (Throwable $cleanupException) {
            report($cleanupException);
        }
    }
}
