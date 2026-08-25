<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\SubmissionStatus;
use App\Exceptions\SubmissionStateConflict;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitLetterSubmission
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    public function execute(User $actor, LetterSubmission $submission): LetterSubmission
    {
        return DB::transaction(function () use ($actor, $submission): LetterSubmission {
            $lockedSubmission = LetterSubmission::query()
                ->lockForUpdate()
                ->whereKey($submission->getKey())
                ->firstOrFail();

            if ($lockedSubmission->status !== SubmissionStatus::Draft) {
                throw SubmissionStateConflict::expectedDraft($lockedSubmission->status);
            }

            if (! $lockedSubmission->document()->exists()) {
                throw ValidationException::withMessages([
                    'document' => 'A PDF document must be uploaded before submission.',
                ]);
            }

            $lockedSubmission->status = SubmissionStatus::Submitted;
            $lockedSubmission->submitted_at = now();
            $lockedSubmission->save();

            $this->recordAudit->execute(
                actor: $actor,
                action: AuditAction::SubmissionSubmitted,
                subjectType: 'letter_submission',
                subjectId: $lockedSubmission->getKey(),
                oldValues: ['status' => SubmissionStatus::Draft->value],
                newValues: [
                    'status' => SubmissionStatus::Submitted->value,
                    'submitted_at' => $lockedSubmission->submitted_at->toISOString(),
                ],
                metadata: ['public_id' => $lockedSubmission->public_id],
            );

            return $lockedSubmission;
        }, attempts: 3);
    }
}
