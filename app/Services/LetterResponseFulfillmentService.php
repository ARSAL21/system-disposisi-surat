<?php

namespace App\Services;

use App\Actions\RecordAudit;
use App\Enums\AuditAction;
use App\Enums\LetterResponseDossierStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\LetterResponseDossier;
use App\Models\OutgoingLetter;
use App\Models\PositionAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;

final class LetterResponseFulfillmentService
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    /** @param Collection<int, OutgoingLetter> $lockedMandates */
    public function fulfillWhenComplete(
        LetterResponseDossier $dossier,
        Collection $lockedMandates,
        User $actor,
        PositionAssignment $assignment,
    ): bool {
        if ($dossier->status !== LetterResponseDossierStatus::Finalized) {
            return false;
        }

        $active = $lockedMandates->filter(
            fn (OutgoingLetter $mandate): bool => $mandate->status !== OutgoingLetterStatus::Withdrawn,
        );

        if ($active->isEmpty()
            || $active->contains(fn (OutgoingLetter $mandate): bool => $mandate->status !== OutgoingLetterStatus::Delivered)) {
            return false;
        }

        $dossier->status = LetterResponseDossierStatus::Fulfilled;
        $dossier->fulfilled_at = Date::now();
        $dossier->fulfilled_by_user_id = $actor->getKey();
        $dossier->fulfilled_by_position_assignment_id = $assignment->getKey();
        $dossier->save();

        $this->recordAudit->execute(
            actor: $actor,
            action: AuditAction::LetterResponseDossierFulfilled,
            subjectType: 'incoming_letter',
            subjectId: $dossier->incoming_letter_id,
            oldValues: ['status' => LetterResponseDossierStatus::Finalized->value],
            newValues: ['status' => LetterResponseDossierStatus::Fulfilled->value],
            actorPositionAssignment: $assignment,
        );

        return true;
    }
}
