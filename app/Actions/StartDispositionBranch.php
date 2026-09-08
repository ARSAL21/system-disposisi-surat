<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\DispositionRecipientStatus;
use App\Exceptions\DispositionStateConflict;
use App\Models\DispositionRecipient;
use App\Models\User;
use App\Services\DispositionBranchLockService;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final class StartDispositionBranch
{
    public function __construct(
        private readonly DispositionBranchLockService $lockService,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(User $actor, DispositionRecipient $branch): DispositionRecipient
    {
        return DB::transaction(function () use ($actor, $branch): DispositionRecipient {
            $context = $this->lockService->lock($actor, $branch);

            if ($context->branch->status !== DispositionRecipientStatus::Pending) {
                throw DispositionStateConflict::staleBranch();
            }

            $context->branch->status = DispositionRecipientStatus::InProgress;
            $context->branch->started_at = Date::now();
            $context->branch->save();

            $this->recordAudit->execute(
                actor: $context->actor,
                action: AuditAction::DispositionStarted,
                subjectType: 'disposition_recipient',
                subjectId: $context->branch->getKey(),
                oldValues: ['recipient_status' => DispositionRecipientStatus::Pending->value],
                newValues: ['recipient_status' => DispositionRecipientStatus::InProgress->value],
                metadata: [
                    'incoming_letter_id' => $context->letter->getKey(),
                    'disposition_id' => $context->branch->disposition_id,
                ],
                actorPositionAssignment: $context->actorPositionAssignment,
            );

            return $context->branch;
        }, attempts: 3);
    }
}
