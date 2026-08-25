<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOnlineSubmission
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $actor, array $attributes): LetterSubmission
    {
        return DB::transaction(function () use ($actor, $attributes): LetterSubmission {
            $submission = new LetterSubmission;
            $submission->public_id = (string) Str::ulid();
            $submission->source = SubmissionSource::Online;
            $submission->status = SubmissionStatus::Draft;
            $submission->submitted_by_user_id = $actor->getKey();
            $submission->recorded_by_user_id = null;
            $submission->sender_organization_name = $attributes['sender_organization_name'];
            $submission->contact_name = $actor->name;
            $submission->contact_email = $actor->email;
            $submission->contact_phone = $attributes['contact_phone'] ?? null;
            $submission->external_letter_number = $attributes['external_letter_number'] ?? null;
            $submission->external_letter_date = $attributes['external_letter_date'] ?? null;
            $submission->subject = $attributes['subject'];
            $submission->summary = $attributes['summary'] ?? null;
            $submission->submitted_at = null;
            $submission->save();

            $this->recordAudit->execute(
                actor: $actor,
                action: AuditAction::SubmissionCreated,
                subjectType: 'letter_submission',
                subjectId: $submission->getKey(),
                newValues: [
                    'source' => SubmissionSource::Online->value,
                    'status' => SubmissionStatus::Draft->value,
                ],
                metadata: ['public_id' => $submission->public_id],
            );

            return $submission;
        }, attempts: 3);
    }
}
