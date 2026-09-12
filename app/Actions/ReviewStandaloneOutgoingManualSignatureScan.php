<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\ManualSignatureReviewDecision;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDocumentVersion;
use App\Models\OutgoingLetterManualSignatureReview;
use App\Models\User;
use App\Services\StandaloneOutgoingFinalDocumentStorage;
use App\Services\StandaloneOutgoingLetterLockService;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Support\Facades\DB;

final class ReviewStandaloneOutgoingManualSignatureScan
{
    public function __construct(
        private readonly StandaloneOutgoingLetterLockService $lockService,
        private readonly StandaloneOutgoingFinalDocumentStorage $storage,
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(User $actor, OutgoingLetter $target, ManualSignatureReviewDecision $decision, ?string $note): OutgoingLetter
    {
        return DB::transaction(function () use ($actor, $target, $decision, $note): OutgoingLetter {
            ['draft' => $draft, 'outgoing' => $outgoing] = $this->lockService->lock($target);
            $version = OutgoingLetterDocumentVersion::query()->where('outgoing_letter_id', $outgoing->getKey())->orderByDesc('version_number')->lockForUpdate()->first();
            if ($outgoing->origin !== OutgoingLetterOrigin::Standalone
                || $outgoing->status !== OutgoingLetterStatus::ManualScanReview
                || $draft->status !== StandaloneOutgoingDraftStatus::ManualScanReview
                || ! $version instanceof OutgoingLetterDocumentVersion
                || OutgoingLetterManualSignatureReview::query()->where('outgoing_letter_document_version_id', $version->getKey())->lockForUpdate()->exists()) {
                throw StandaloneOutgoingStateConflict::stale();
            }
            $this->storage->validate($outgoing, $version);

            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $assignment = $this->assignmentResolver->lockSectionHeadAssignmentForUnit($lockedActor, $draft->organizational_unit_id);
            $review = new OutgoingLetterManualSignatureReview;
            $review->outgoing_letter_document_version_id = $version->getKey();
            $review->decision = $decision;
            $review->note = $note;
            $review->reviewed_by_user_id = $lockedActor->getKey();
            $review->reviewed_by_position_assignment_id = $assignment->getKey();
            $review->save();
            $outgoing->status = $decision === ManualSignatureReviewDecision::Verified
                ? OutgoingLetterStatus::ReadyForDelivery
                : OutgoingLetterStatus::RevisionRequired;
            $outgoing->save();
            $draft->status = $decision === ManualSignatureReviewDecision::Verified
                ? StandaloneOutgoingDraftStatus::ReadyForDelivery
                : StandaloneOutgoingDraftStatus::RevisionRequired;
            $draft->save();

            $this->audit->execute(
                actor: $lockedActor,
                action: AuditAction::StandaloneOutgoingManualScanReviewed,
                subjectType: 'standalone_outgoing_draft',
                subjectId: $draft->getKey(),
                oldValues: ['status' => OutgoingLetterStatus::ManualScanReview->value],
                newValues: ['outgoing_letter_id' => $outgoing->getKey(), 'status' => $outgoing->status->value, 'decision' => $decision->value],
                actorPositionAssignment: $assignment,
            );

            return $outgoing;
        }, attempts: 3);
    }
}
