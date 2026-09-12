<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\LetterResponseDossierStatus;
use App\Enums\OutgoingLetterStatus;
use App\Exceptions\OutgoingLetterStateConflict;
use App\LetterResponses\LetterResponseSekdaPositionResolver;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\LetterResponsePositionAssignmentResolver;
use App\Services\OutgoingLetterLockService;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final class WithdrawOutgoingLetterMandate
{
    public function __construct(
        private readonly OutgoingLetterLockService $lockService,
        private readonly LetterResponsePositionAssignmentResolver $assignmentResolver,
        private readonly LetterResponseSekdaPositionResolver $sekdaPositionResolver,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(User $actor, OutgoingLetter $target, string $reason): OutgoingLetter
    {
        return DB::transaction(function () use ($actor, $target, $reason): OutgoingLetter {
            $context = $this->lockService->lock($target);
            $outgoing = $context['outgoing'];
            $dossier = $context['dossier'];

            if ($outgoing->status !== OutgoingLetterStatus::Authorized
                || ! in_array($dossier->status, [
                    LetterResponseDossierStatus::Open,
                    LetterResponseDossierStatus::Finalized,
                ], true)) {
                throw OutgoingLetterStateConflict::stale();
            }

            $activeCount = $context['mandates']->where('status', '!=', OutgoingLetterStatus::Withdrawn)->count();
            if ($dossier->status === LetterResponseDossierStatus::Finalized && $activeCount <= 1) {
                throw OutgoingLetterStateConflict::lastActiveMandate();
            }

            $executivePositionId = $this->sekdaPositionResolver->lockPositionId($context['letter']);

            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $assignment = $this->assignmentResolver->lockAssignmentForPosition($lockedActor, $executivePositionId);
            $outgoing->status = OutgoingLetterStatus::Withdrawn;
            $outgoing->withdrawal_reason = trim($reason);
            $outgoing->withdrawn_by_user_id = $lockedActor->getKey();
            $outgoing->withdrawn_by_position_assignment_id = $assignment->getKey();
            $outgoing->withdrawn_at = Date::now();
            $outgoing->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::OutgoingLetterMandateWithdrawn,
                subjectType: 'incoming_letter',
                subjectId: $context['letter']->getKey(),
                oldValues: ['status' => OutgoingLetterStatus::Authorized->value],
                newValues: [
                    'outgoing_letter_id' => $outgoing->getKey(),
                    'status' => OutgoingLetterStatus::Withdrawn->value,
                ],
                actorPositionAssignment: $assignment,
            );

            return $outgoing;
        }, attempts: 3);
    }
}
