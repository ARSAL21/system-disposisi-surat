<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\DispositionRecipientStatus;
use App\Exceptions\DispositionStateConflict;
use App\Models\DispositionFollowUp;
use App\Models\DispositionRecipient;
use App\Models\User;
use App\Services\DispositionBranchLockService;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final class AddDispositionFollowUp
{
    public function __construct(
        private readonly DispositionBranchLockService $lockService,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(User $actor, DispositionRecipient $branch, string $note): DispositionFollowUp
    {
        return DB::transaction(function () use ($actor, $branch, $note): DispositionFollowUp {
            $context = $this->lockService->lock($actor, $branch);

            if ($context->branch->status !== DispositionRecipientStatus::InProgress) {
                throw DispositionStateConflict::staleBranch();
            }

            $followUp = new DispositionFollowUp;
            $followUp->disposition_recipient_id = $context->branch->getKey();
            $followUp->created_by_user_id = $context->actor->getKey();
            $followUp->created_by_position_assignment_id = $context->actorPositionAssignment->getKey();
            $followUp->note = $note;
            $followUp->created_at = Date::now();
            $followUp->save();

            $this->recordAudit->execute(
                actor: $context->actor,
                action: AuditAction::FollowUpAdded,
                subjectType: 'disposition_follow_up',
                subjectId: $followUp->getKey(),
                newValues: ['recipient_status' => DispositionRecipientStatus::InProgress->value],
                metadata: [
                    'incoming_letter_id' => $context->letter->getKey(),
                    'disposition_id' => $context->branch->disposition_id,
                    'disposition_recipient_id' => $context->branch->getKey(),
                ],
                actorPositionAssignment: $context->actorPositionAssignment,
            );

            return $followUp;
        }, attempts: 3);
    }
}
