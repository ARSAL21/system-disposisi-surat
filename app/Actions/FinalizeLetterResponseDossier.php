<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\IncomingLetterStatus;
use App\Enums\LetterResponseDossierStatus;
use App\Enums\OutgoingLetterStatus;
use App\Exceptions\LetterResponseStateConflict;
use App\LetterResponses\LetterResponseSekdaPositionResolver;
use App\Models\IncomingLetter;
use App\Models\LetterResponseDossier;
use App\Models\LetterResponseReview;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\LetterResponsePositionAssignmentResolver;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final class FinalizeLetterResponseDossier
{
    public function __construct(
        private readonly LetterResponsePositionAssignmentResolver $assignmentResolver,
        private readonly LetterResponseSekdaPositionResolver $sekdaPositionResolver,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(User $actor, LetterResponseDossier $dossier): LetterResponseDossier
    {
        return DB::transaction(function () use ($actor, $dossier): LetterResponseDossier {
            $letter = IncomingLetter::query()->whereKey($dossier->incoming_letter_id)->lockForUpdate()->firstOrFail();
            $lockedDossier = LetterResponseDossier::query()->whereKey($dossier->getKey())->lockForUpdate()->firstOrFail();
            $mandates = $lockedDossier->mandates()
                ->with('sourceDocumentVersion.document')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($letter->status !== IncomingLetterStatus::Completed
                || $lockedDossier->status !== LetterResponseDossierStatus::Open) {
                throw LetterResponseStateConflict::staleDossier();
            }

            $activeMandates = $mandates->filter(
                fn (OutgoingLetter $mandate): bool => $mandate->status !== OutgoingLetterStatus::Withdrawn,
            );

            if ($activeMandates->isEmpty()) {
                throw LetterResponseStateConflict::missingMandate();
            }

            if ($mandates->contains(function (OutgoingLetter $mandate) use ($letter, $lockedDossier): bool {
                $version = $mandate->sourceDocumentVersion;
                $validStatus = $mandate->status === OutgoingLetterStatus::Authorized
                    || ($mandate->status === OutgoingLetterStatus::Withdrawn
                        && $mandate->outgoing_number === null
                        && $mandate->withdrawn_at !== null);

                return (int) $mandate->incoming_letter_id !== (int) $letter->getKey()
                    || (int) $mandate->letter_response_dossier_id !== (int) $lockedDossier->getKey()
                    || ! $validStatus
                    || (int) $version->document->letter_response_dossier_id !== (int) $lockedDossier->getKey()
                    || LetterResponseReview::query()->where('document_version_id', $version->getKey())->exists();
            })) {
                throw LetterResponseStateConflict::staleDossier();
            }

            $executivePositionId = $this->sekdaPositionResolver->lockPositionId($letter);

            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $assignment = $this->assignmentResolver->lockAssignmentForPosition($lockedActor, $executivePositionId);
            $lockedDossier->status = LetterResponseDossierStatus::Finalized;
            $lockedDossier->finalized_at = Date::now();
            $lockedDossier->finalized_by_user_id = $lockedActor->getKey();
            $lockedDossier->finalized_by_position_assignment_id = $assignment->getKey();
            $lockedDossier->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::LetterResponseDossierFinalized,
                subjectType: 'incoming_letter',
                subjectId: $letter->getKey(),
                oldValues: ['status' => LetterResponseDossierStatus::Open->value],
                newValues: ['status' => LetterResponseDossierStatus::Finalized->value],
                actorPositionAssignment: $assignment,
            );

            return $lockedDossier;
        }, attempts: 3);
    }
}
