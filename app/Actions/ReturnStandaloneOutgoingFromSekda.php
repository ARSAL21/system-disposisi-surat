<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Enums\StandaloneOutgoingSekdaDecisionType;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\OutgoingLetter;
use App\Models\StandaloneOutgoingSekdaDecision;
use App\Models\User;
use App\Services\StandaloneOutgoingLetterLockService;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Support\Facades\DB;

final class ReturnStandaloneOutgoingFromSekda
{
    public function __construct(
        private readonly StandaloneOutgoingLetterLockService $lockService,
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(User $actor, OutgoingLetter $target, string $reason): OutgoingLetter
    {
        return DB::transaction(function () use ($actor, $target, $reason): OutgoingLetter {
            ['draft' => $draft, 'outgoing' => $outgoing] = $this->lockService->lock($target);
            if ($outgoing->origin !== OutgoingLetterOrigin::Standalone
                || $outgoing->status !== OutgoingLetterStatus::SekdaReview
                || $draft->status !== StandaloneOutgoingDraftStatus::SekdaReview) {
                throw StandaloneOutgoingStateConflict::stale();
            }

            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $assignment = $this->assignmentResolver->lockSekdaAssignment($lockedActor);
            $decision = new StandaloneOutgoingSekdaDecision;
            $decision->outgoing_letter_id = $outgoing->getKey();
            $decision->decision = StandaloneOutgoingSekdaDecisionType::Returned;
            $decision->note = trim($reason);
            $decision->decided_by_user_id = $lockedActor->getKey();
            $decision->decided_by_position_assignment_id = $assignment->getKey();
            $decision->save();
            $outgoing->status = OutgoingLetterStatus::RevisionRequired;
            $outgoing->save();
            $draft->status = StandaloneOutgoingDraftStatus::RevisionRequired;
            $draft->save();

            $this->audit->execute(
                actor: $lockedActor,
                action: AuditAction::StandaloneOutgoingReturnedBySekda,
                subjectType: 'standalone_outgoing_draft',
                subjectId: $draft->getKey(),
                oldValues: ['status' => OutgoingLetterStatus::SekdaReview->value],
                newValues: ['outgoing_letter_id' => $outgoing->getKey(), 'status' => OutgoingLetterStatus::RevisionRequired->value],
                actorPositionAssignment: $assignment,
            );

            return $outgoing;
        }, attempts: 3);
    }
}
