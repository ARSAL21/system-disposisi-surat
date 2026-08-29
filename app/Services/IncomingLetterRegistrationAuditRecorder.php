<?php

namespace App\Services;

use App\Actions\RecordAudit;
use App\Enums\AuditAction;
use App\Enums\IncomingLetterStatus;
use App\Enums\SubmissionStatus;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\LetterSubmission;
use App\Models\PositionAssignment;
use App\Models\User;

class IncomingLetterRegistrationAuditRecorder
{
    public function __construct(
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(
        User $actor,
        PositionAssignment $positionAssignment,
        LetterSubmission $submission,
        IncomingLetter $incomingLetter,
        LetterDocument $letterDocument,
        int $decisionId,
    ): void {
        $this->recordAudit->execute(
            actor: $actor,
            action: AuditAction::LetterRegistered,
            subjectType: 'incoming_letter',
            subjectId: $incomingLetter->getKey(),
            oldValues: ['submission_status' => SubmissionStatus::ReadyForApproval->value],
            newValues: [
                'submission_status' => SubmissionStatus::Registered->value,
                'letter_status' => IncomingLetterStatus::Registered->value,
                'agenda_number' => $incomingLetter->agenda_number,
                'agenda_year' => $incomingLetter->agenda_year,
            ],
            metadata: [
                'submission_id' => $submission->getKey(),
                'submission_public_id' => $submission->public_id,
                'submission_decision_id' => $decisionId,
            ],
            actorPositionAssignment: $positionAssignment,
        );

        $this->recordAudit->execute(
            actor: $actor,
            action: AuditAction::DocumentVersionCreated,
            subjectType: 'letter_document',
            subjectId: $letterDocument->getKey(),
            newValues: [
                'incoming_letter_id' => $incomingLetter->getKey(),
                'version_number' => 1,
                'sha256' => $letterDocument->sha256,
                'source_submission_document_id' => $letterDocument->source_submission_document_id,
            ],
            actorPositionAssignment: $positionAssignment,
        );
    }
}
