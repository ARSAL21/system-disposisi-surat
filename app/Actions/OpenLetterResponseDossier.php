<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\LetterResponseDossierStatus;
use App\Models\IncomingLetter;
use App\Models\LetterResponseDossier;
use App\Models\PositionAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Date;

final class OpenLetterResponseDossier
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    public function executeLocked(
        IncomingLetter $letter,
        User $actor,
        PositionAssignment $assignment,
    ): LetterResponseDossier {
        $existing = LetterResponseDossier::query()
            ->where('incoming_letter_id', $letter->getKey())
            ->lockForUpdate()
            ->first();

        if ($existing instanceof LetterResponseDossier) {
            return $existing;
        }

        $dossier = new LetterResponseDossier;
        $dossier->incoming_letter_id = $letter->getKey();
        $dossier->status = LetterResponseDossierStatus::Open;
        $dossier->opened_at = Date::now();
        $dossier->finalized_at = null;
        $dossier->finalized_by_user_id = null;
        $dossier->finalized_by_position_assignment_id = null;
        $dossier->save();

        $this->recordAudit->execute(
            actor: $actor,
            action: AuditAction::LetterResponseDossierOpened,
            subjectType: 'incoming_letter',
            subjectId: $letter->getKey(),
            newValues: [
                'incoming_letter_id' => $letter->getKey(),
                'status' => LetterResponseDossierStatus::Open->value,
            ],
            actorPositionAssignment: $assignment,
        );

        return $dossier;
    }
}
