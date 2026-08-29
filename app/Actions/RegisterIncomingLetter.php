<?php

namespace App\Actions;

use App\Enums\IncomingLetterStatus;
use App\Enums\SubmissionDecisionOutcome;
use App\Enums\SubmissionStatus;
use App\Exceptions\SubmissionStateConflict;
use App\Models\IncomingLetter;
use App\Models\LetterSubmission;
use App\Models\SubmissionDocument;
use App\Models\User;
use App\Services\CreateSubmissionDecision;
use App\Services\IncomingLetterRegistrationAuditRecorder;
use App\Services\InitialLetterDocumentCreator;
use App\Services\IntakeApprovalPositionAssignmentResolver;
use App\Services\SenderOrganizationResolver;
use App\Services\SubmissionDocumentIntegrityVerifier;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterIncomingLetter
{
    public function __construct(
        private readonly IntakeApprovalPositionAssignmentResolver $positionAssignmentResolver,
        private readonly SubmissionDocumentIntegrityVerifier $documentIntegrityVerifier,
        private readonly SenderOrganizationResolver $senderOrganizationResolver,
        private readonly InitialLetterDocumentCreator $initialDocumentCreator,
        private readonly CreateSubmissionDecision $createDecision,
        private readonly IncomingLetterRegistrationAuditRecorder $auditRecorder,
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
                $senderOrganization = $this->senderOrganizationResolver
                    ->resolveForRegistration($attributes['sender_organization']);
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

                $letterDocument = $this->initialDocumentCreator->execute($incomingLetter, $document);
                $decision = $this->createDecision->execute(
                    actor: $actor,
                    positionAssignment: $positionAssignment,
                    submission: $lockedSubmission,
                    outcome: SubmissionDecisionOutcome::Registered,
                    note: $attributes['note'],
                );

                $this->auditRecorder->execute(
                    actor: $actor,
                    positionAssignment: $positionAssignment,
                    submission: $lockedSubmission,
                    incomingLetter: $incomingLetter,
                    letterDocument: $letterDocument,
                    decisionId: $decision->getKey(),
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
