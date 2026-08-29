<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Exceptions\SubmissionStateConflict;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateSubmissionDraft
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $actor, LetterSubmission $submission, array $attributes): LetterSubmission
    {
        return DB::transaction(function () use ($actor, $submission, $attributes): LetterSubmission {
            $lockedSubmission = LetterSubmission::query()
                ->lockForUpdate()
                ->whereKey($submission->getKey())
                ->firstOrFail();

            if (! $lockedSubmission->isPubliclyEditable()) {
                throw SubmissionStateConflict::expectedPubliclyEditable($lockedSubmission->status);
            }

            $originalValues = Arr::only($lockedSubmission->getAttributes(), array_keys($attributes));

            foreach ($attributes as $field => $value) {
                $lockedSubmission->setAttribute($field, $value);
            }

            $changedFields = array_keys($lockedSubmission->getDirty());

            if ($changedFields !== []) {
                $oldValues = Arr::only($originalValues, $changedFields);
                $newValues = Arr::only($lockedSubmission->getAttributes(), $changedFields);
                $lockedSubmission->save();

                $this->recordAudit->execute(
                    actor: $actor,
                    action: AuditAction::SubmissionUpdated,
                    subjectType: 'letter_submission',
                    subjectId: $lockedSubmission->getKey(),
                    oldValues: $oldValues,
                    newValues: $newValues,
                    metadata: [
                        'public_id' => $lockedSubmission->public_id,
                        'changed_fields' => $changedFields,
                    ],
                );
            }

            return $lockedSubmission;
        }, attempts: 3);
    }
}
