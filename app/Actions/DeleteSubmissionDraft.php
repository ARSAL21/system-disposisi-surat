<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\SubmissionStatus;
use App\Exceptions\SubmissionStateConflict;
use App\Models\LetterSubmission;
use App\Models\SubmissionDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DeleteSubmissionDraft
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    public function execute(User $actor, LetterSubmission $submission): void
    {
        $storedDocument = DB::transaction(function () use ($actor, $submission): ?array {
            $lockedSubmission = LetterSubmission::query()
                ->lockForUpdate()
                ->whereKey($submission->getKey())
                ->firstOrFail();

            if ($lockedSubmission->status !== SubmissionStatus::Draft) {
                throw SubmissionStateConflict::expectedDraft($lockedSubmission->status);
            }

            $document = $lockedSubmission->document()->first();

            $this->recordAudit->execute(
                actor: $actor,
                action: AuditAction::SubmissionDraftDeleted,
                subjectType: 'letter_submission',
                subjectId: $lockedSubmission->getKey(),
                oldValues: ['status' => SubmissionStatus::Draft->value],
                metadata: ['public_id' => $lockedSubmission->public_id],
            );

            if ($document instanceof SubmissionDocument) {
                $documentReference = [
                    'disk' => $document->storage_disk,
                    'path' => $document->storage_path,
                ];
                $document->delete();
            }

            $lockedSubmission->delete();

            return $documentReference ?? null;
        }, attempts: 3);

        if ($storedDocument !== null) {
            try {
                Storage::disk($storedDocument['disk'])->delete($storedDocument['path']);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
