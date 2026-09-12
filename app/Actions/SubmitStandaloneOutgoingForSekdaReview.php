<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\OutgoingLetterPositionAssignmentResolver;
use App\Services\StandaloneOutgoingLetterLockService;
use Illuminate\Support\Facades\DB;

final class SubmitStandaloneOutgoingForSekdaReview
{
    public function __construct(
        private readonly StandaloneOutgoingLetterLockService $lockService,
        private readonly OutgoingLetterPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(User $actor, OutgoingLetter $target): OutgoingLetter
    {
        return DB::transaction(function () use ($actor, $target): OutgoingLetter {
            ['draft' => $draft, 'outgoing' => $outgoing] = $this->lockService->lock($target);
            if ($outgoing->origin !== OutgoingLetterOrigin::Standalone
                || $outgoing->status !== OutgoingLetterStatus::NumberAssigned
                || $draft->status !== StandaloneOutgoingDraftStatus::NumberAssigned) {
                throw StandaloneOutgoingStateConflict::stale();
            }

            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $assignment = $this->assignmentResolver->lockGeneralAffairsOfficer($lockedActor);
            $outgoing->status = OutgoingLetterStatus::SekdaReview;
            $outgoing->save();
            $draft->status = StandaloneOutgoingDraftStatus::SekdaReview;
            $draft->save();

            $this->audit->execute(
                actor: $lockedActor,
                action: AuditAction::StandaloneOutgoingSubmittedToSekda,
                subjectType: 'standalone_outgoing_draft',
                subjectId: $draft->getKey(),
                oldValues: ['status' => OutgoingLetterStatus::NumberAssigned->value],
                newValues: ['status' => OutgoingLetterStatus::SekdaReview->value, 'outgoing_letter_id' => $outgoing->getKey()],
                actorPositionAssignment: $assignment,
            );

            return $outgoing;
        }, attempts: 3);
    }
}
