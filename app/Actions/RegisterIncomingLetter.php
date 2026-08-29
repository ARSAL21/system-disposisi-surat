<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\IncomingLetterStatus;
use App\Enums\SubmissionDecisionOutcome;
use App\Enums\SubmissionStatus;
use App\Exceptions\SubmissionStateConflict;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\LetterSubmission;
use App\Models\PositionAssignment;
use App\Models\SenderOrganization;
use App\Models\SubmissionDocument;
use App\Models\User;
use App\Services\CreateSubmissionDecision;
use App\Services\IntakeApprovalPositionAssignmentResolver;
use App\Services\SubmissionDocumentIntegrityVerifier;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterIncomingLetter
{
    public function __construct(
        private readonly IntakeApprovalPositionAssignmentResolver $positionAssignmentResolver,
        private readonly SubmissionDocumentIntegrityVerifier $documentIntegrityVerifier,
        private readonly CreateSubmissionDecision $createDecision,
        private readonly RecordAudit $recordAudit,
    ) {}

    /**
     * @param array{
     *     agenda_number: string,
     *     note: string|null,
     *     sender_organization: array{mode: 'existing', id: int}|array{mode: 'new', name: string, address: string|null, contact: string|null}
     * } $attributes
     */
    public function execute(User $actor, LetterSubmission $submission, array $attributes): IncomingLetter
    {
        try {
            return DB::transaction(function () use ($actor, $submission, $attributes): IncomingLetter {
                $lockedSubmission = LetterSubmission::query()
                    ->whereKey($submission->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedSubmission->status !== SubmissionStatus::ReadyForApproval) {
                    throw SubmissionStateConflict::expectedReadyForApproval($lockedSubmission->status);
                }

                $document = SubmissionDocument::query()
                    ->where('letter_submission_id', $lockedSubmission->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $document instanceof SubmissionDocument) {
                    throw ValidationException::withMessages([
                        'document' => 'Dokumen PDF wajib tersedia sebelum registrasi.',
                    ]);
                }

                $this->documentIntegrityVerifier->verify($document);
                $positionAssignment = $this->positionAssignmentResolver->lockActiveAssignment($actor);
                $senderOrganization = $this->resolveSenderOrganization($attributes['sender_organization']);
                $receivedAt = now();
                $agendaYear = (int) $receivedAt->year;
                $agendaNumber = trim($attributes['agenda_number']);

                if (IncomingLetter::query()
                    ->where('agenda_year', $agendaYear)
                    ->where('agenda_number', $agendaNumber)
                    ->lockForUpdate()
                    ->exists()) {
                    $this->throwDuplicateAgendaNumber();
                }

                $incomingLetter = new IncomingLetter;
                $incomingLetter->letter_submission_id = $lockedSubmission->getKey();
                $incomingLetter->agenda_number = $agendaNumber;
                $incomingLetter->agenda_year = $agendaYear;
                $incomingLetter->sender_organization_id = $senderOrganization->getKey();
                $incomingLetter->external_letter_number = $lockedSubmission->external_letter_number;
                $incomingLetter->external_letter_date = $lockedSubmission->external_letter_date;
                $incomingLetter->subject = $lockedSubmission->subject;
                $incomingLetter->summary = $lockedSubmission->summary;
                $incomingLetter->received_at = $receivedAt;
                $incomingLetter->status = IncomingLetterStatus::Registered;
                $incomingLetter->registered_by_user_id = $actor->getKey();
                $incomingLetter->registered_by_position_assignment_id = $positionAssignment->getKey();
                $incomingLetter->save();

                $letterDocument = $this->createInitialDocumentVersion($incomingLetter, $document);
                $decision = $this->createDecision->execute(
                    actor: $actor,
                    positionAssignment: $positionAssignment,
                    submission: $lockedSubmission,
                    outcome: SubmissionDecisionOutcome::Registered,
                    note: $attributes['note'],
                );

                $this->recordRegistrationAudits(
                    actor: $actor,
                    submission: $lockedSubmission,
                    incomingLetter: $incomingLetter,
                    letterDocument: $letterDocument,
                    decisionId: $decision->getKey(),
                    positionAssignment: $positionAssignment,
                );

                return $incomingLetter;
            }, attempts: 3);
        } catch (QueryException $exception) {
            if ($this->isAgendaUniqueViolation($exception)) {
                $this->throwDuplicateAgendaNumber();
            }

            throw $exception;
        }
    }

    /**
     * @param  array{mode: 'existing', id: int}|array{mode: 'new', name: string, address: string|null, contact: string|null}  $selection
     */
    private function resolveSenderOrganization(array $selection): SenderOrganization
    {
        if ($selection['mode'] === 'existing') {
            return SenderOrganization::query()
                ->whereKey($selection['id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();
        }

        $organization = new SenderOrganization;
        $organization->name = trim($selection['name']);
        $organization->address = $this->nullableTrim($selection['address']);
        $organization->contact = $this->nullableTrim($selection['contact']);
        $organization->is_active = true;
        $organization->save();

        return $organization;
    }

    private function createInitialDocumentVersion(
        IncomingLetter $incomingLetter,
        SubmissionDocument $sourceDocument,
    ): LetterDocument {
        $document = new LetterDocument;
        $document->incoming_letter_id = $incomingLetter->getKey();
        $document->source_submission_document_id = $sourceDocument->getKey();
        $document->version_number = 1;
        $document->replaces_document_id = null;
        $document->storage_disk = $sourceDocument->storage_disk;
        $document->storage_path = $sourceDocument->storage_path;
        $document->original_filename = $sourceDocument->original_filename;
        $document->mime_type = $sourceDocument->mime_type;
        $document->size_bytes = $sourceDocument->size_bytes;
        $document->sha256 = strtolower($sourceDocument->sha256);
        $document->correction_reason = null;
        $document->uploaded_by_user_id = $sourceDocument->uploaded_by_user_id;
        $document->save();

        return $document;
    }

    private function recordRegistrationAudits(
        User $actor,
        LetterSubmission $submission,
        IncomingLetter $incomingLetter,
        LetterDocument $letterDocument,
        int $decisionId,
        PositionAssignment $positionAssignment,
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

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function isAgendaUniqueViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'incoming_letters_agenda_year_agenda_number_unique')
            || str_contains($message, 'incoming_letters.agenda_year, incoming_letters.agenda_number');
    }

    private function throwDuplicateAgendaNumber(): never
    {
        throw ValidationException::withMessages([
            'agenda_number' => 'Nomor agenda sudah digunakan pada tahun berjalan.',
        ]);
    }
}
