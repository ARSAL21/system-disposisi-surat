<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\SubmissionReviewOutcome;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
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
use RuntimeException;
use Throwable;

final class CreateManualSubmission
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
    public function execute(User $actor, array $attributes, UploadedFile $file): LetterSubmission
    {
        $publicId = (string) Str::ulid();
        $fileData = $this->storeFile($publicId, $file);

        try {
            return DB::transaction(function () use ($actor, $attributes, $fileData, $publicId): LetterSubmission {
                $positionAssignment = $this->positionAssignmentResolver->lockActiveAssignment($actor);
                $submittedAt = now();

                $submission = new LetterSubmission;
                $submission->public_id = $publicId;
                $submission->source = SubmissionSource::Manual;
                $submission->status = SubmissionStatus::ReadyForApproval;
                $submission->submitted_by_user_id = null;
                $submission->recorded_by_user_id = $actor->getKey();
                $submission->sender_organization_name = $attributes['sender_organization_name'];
                $submission->contact_name = $attributes['contact_name'];
                $submission->contact_email = $attributes['contact_email'];
                $submission->contact_phone = $attributes['contact_phone'];
                $submission->external_letter_number = $attributes['external_letter_number'];
                $submission->external_letter_date = $attributes['external_letter_date'];
                $submission->subject = $attributes['subject'];
                $submission->summary = $attributes['summary'];
                $submission->received_at = $attributes['received_at'];
                $submission->submitted_at = $submittedAt;
                $submission->save();

                $document = new SubmissionDocument;
                $document->letter_submission_id = $submission->getKey();
                $document->storage_disk = self::DISK;
                $document->storage_path = $fileData['path'];
                $document->original_filename = $fileData['original_filename'];
                $document->mime_type = $fileData['mime_type'];
                $document->size_bytes = $fileData['size_bytes'];
                $document->sha256 = $fileData['sha256'];
                $document->uploaded_by_user_id = $actor->getKey();
                $document->save();

                $review = new SubmissionReview;
                $review->letter_submission_id = $submission->getKey();
                $review->outcome = SubmissionReviewOutcome::ReadyForApproval;
                $review->checklist = SubmissionScreeningChecklist::normalize($attributes['checklist']);
                $review->note = $attributes['screening_note'];
                $review->created_by_user_id = $actor->getKey();
                $review->created_by_position_assignment_id = $positionAssignment->getKey();
                $review->save();

                $this->recordAudit->execute(
                    actor: $actor,
                    action: AuditAction::ManualSubmissionCreated,
                    subjectType: 'letter_submission',
                    subjectId: $submission->getKey(),
                    newValues: [
                        'source' => SubmissionSource::Manual->value,
                        'status' => SubmissionStatus::ReadyForApproval->value,
                        'received_at' => $submission->received_at->toISOString(),
                        'submitted_at' => $submission->submitted_at->toISOString(),
                        'sha256' => $document->sha256,
                        'size_bytes' => $document->size_bytes,
                    ],
                    metadata: [
                        'public_id' => $submission->public_id,
                        'submission_document_id' => $document->getKey(),
                        'submission_review_id' => $review->getKey(),
                    ],
                    actorPositionAssignment: $positionAssignment,
                );

                return $submission->load(['document', 'latestReview']);
            }, attempts: 3);
        } catch (Throwable $exception) {
            $this->deleteCompensationFile($fileData['path']);

            throw $exception;
        }
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

        return [
            'path' => $path,
            'original_filename' => $this->safeOriginalFilename($file),
            'mime_type' => $mime,
            'size_bytes' => (int) $size,
            'sha256' => $sha256,
        ];
    }

    private function safeOriginalFilename(UploadedFile $file): string
    {
        $filename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?: 'document.pdf';

        return Str::substr($filename, 0, 255);
    }

    private function deleteCompensationFile(string $path): void
    {
        try {
            Storage::disk(self::DISK)->delete($path);
        } catch (Throwable $cleanupException) {
            report($cleanupException);
        }
    }
}
