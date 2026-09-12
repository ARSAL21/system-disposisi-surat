<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\OutgoingLetterElectronicApprovalMethod;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Enums\StandaloneOutgoingSekdaDecisionType;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterElectronicApproval;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\Models\StandaloneOutgoingSekdaDecision;
use App\Models\User;
use App\Services\StandaloneOutgoingDocumentStorage;
use App\Services\StandaloneOutgoingLetterLockService;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Support\Facades\DB;

final class ChooseStandaloneOutgoingManualSignature
{
    public function __construct(
        private readonly StandaloneOutgoingLetterLockService $lockService,
        private readonly StandaloneOutgoingDocumentStorage $storage,
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(User $actor, OutgoingLetter $target): OutgoingLetter
    {
        return DB::transaction(function () use ($actor, $target): OutgoingLetter {
            ['draft' => $draft, 'outgoing' => $outgoing] = $this->lockService->lock($target);
            $source = StandaloneOutgoingDocumentVersion::query()->where('standalone_outgoing_draft_id', $draft->getKey())->orderByDesc('version_number')->lockForUpdate()->firstOrFail();
            $this->storage->validate($draft, $source);
            if ($outgoing->origin !== OutgoingLetterOrigin::Standalone
                || $outgoing->status !== OutgoingLetterStatus::SekdaReview
                || $draft->status !== StandaloneOutgoingDraftStatus::SekdaReview
                || OutgoingLetterElectronicApproval::query()
                    ->where('outgoing_letter_id', $outgoing->getKey())
                    ->where('source_document_sha256', $source->sha256)
                    ->lockForUpdate()
                    ->exists()) {
                throw StandaloneOutgoingStateConflict::stale();
            }

            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $assignment = $this->assignmentResolver->lockSekdaAssignment($lockedActor);
            $approval = new OutgoingLetterElectronicApproval;
            $approval->outgoing_letter_id = $outgoing->getKey();
            $approval->method = OutgoingLetterElectronicApprovalMethod::ManualSignature;
            $approval->source_document_sha256 = $source->sha256;
            $approval->approved_by_user_id = $lockedActor->getKey();
            $approval->approved_by_position_assignment_id = $assignment->getKey();
            $approval->approved_at = now();
            $approval->save();

            $decision = new StandaloneOutgoingSekdaDecision;
            $decision->outgoing_letter_id = $outgoing->getKey();
            $decision->decision = StandaloneOutgoingSekdaDecisionType::ManualSignatureSelected;
            $decision->decided_by_user_id = $lockedActor->getKey();
            $decision->decided_by_position_assignment_id = $assignment->getKey();
            $decision->save();
            $outgoing->status = OutgoingLetterStatus::AwaitingManualSignature;
            $outgoing->save();
            $draft->status = StandaloneOutgoingDraftStatus::AwaitingManualSignature;
            $draft->save();

            $this->audit->execute(
                actor: $lockedActor,
                action: AuditAction::StandaloneOutgoingManualSignatureSelected,
                subjectType: 'standalone_outgoing_draft',
                subjectId: $draft->getKey(),
                oldValues: ['status' => OutgoingLetterStatus::SekdaReview->value],
                newValues: ['outgoing_letter_id' => $outgoing->getKey(), 'status' => OutgoingLetterStatus::AwaitingManualSignature->value],
                actorPositionAssignment: $assignment,
            );

            return $outgoing;
        }, attempts: 3);
    }
}
