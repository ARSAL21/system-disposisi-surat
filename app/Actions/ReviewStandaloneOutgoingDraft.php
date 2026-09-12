<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\OutgoingLetterStatus;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Enums\StandaloneOutgoingReviewDecision;
use App\Enums\StandaloneOutgoingReviewStage;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\OutgoingLetter;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\Models\StandaloneOutgoingDraft;
use App\Models\StandaloneOutgoingReview;
use App\Models\User;
use App\Services\StandaloneOutgoingDocumentStorage;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Support\Facades\DB;

final class ReviewStandaloneOutgoingDraft
{
    public function __construct(
        private readonly StandaloneOutgoingDocumentStorage $storage,
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(
        User $actor,
        StandaloneOutgoingDraft $draft,
        StandaloneOutgoingReviewStage $stage,
        StandaloneOutgoingReviewDecision $decision,
        ?string $reason,
    ): StandaloneOutgoingDraft {
        return DB::transaction(function () use ($actor, $draft, $stage, $decision, $reason): StandaloneOutgoingDraft {
            $lockedDraft = StandaloneOutgoingDraft::query()->whereKey($draft->getKey())->lockForUpdate()->firstOrFail();
            $expectedStatus = $stage === StandaloneOutgoingReviewStage::SectionHead
                ? StandaloneOutgoingDraftStatus::SectionReview
                : StandaloneOutgoingDraftStatus::AssistantReview;

            if ($lockedDraft->status !== $expectedStatus) {
                throw StandaloneOutgoingStateConflict::stale();
            }

            $relatedOutgoing = OutgoingLetter::query()
                ->where('standalone_outgoing_draft_id', $lockedDraft->getKey())
                ->lockForUpdate()
                ->first();

            $version = StandaloneOutgoingDocumentVersion::query()
                ->where('standalone_outgoing_draft_id', $lockedDraft->getKey())
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->firstOrFail();
            $this->storage->validate($lockedDraft, $version);

            if (StandaloneOutgoingReview::query()
                ->where('standalone_outgoing_document_version_id', $version->getKey())
                ->where('stage', $stage->value)
                ->lockForUpdate()
                ->exists()) {
                throw StandaloneOutgoingStateConflict::stale();
            }

            // Keep the publication lock sequence stable: draft, document, actor assignment, audit.
            $assignment = $stage === StandaloneOutgoingReviewStage::SectionHead
                ? $this->assignmentResolver->lockSectionHeadAssignmentForUnit($actor, $lockedDraft->organizational_unit_id)
                : $this->assignmentResolver->lockAssistantAssignmentForUnit($actor, $lockedDraft->organizational_unit_id);

            $review = new StandaloneOutgoingReview;
            $review->standalone_outgoing_draft_id = $lockedDraft->getKey();
            $review->standalone_outgoing_document_version_id = $version->getKey();
            $review->stage = $stage;
            $review->decision = $decision;
            $review->reason = $reason;
            $review->decided_by_user_id = $actor->getKey();
            $review->decided_by_position_assignment_id = $assignment->getKey();
            $review->save();

            $oldStatus = $lockedDraft->status->value;
            $nextStatus = match ($decision) {
                StandaloneOutgoingReviewDecision::Returned => StandaloneOutgoingDraftStatus::RevisionRequired,
                StandaloneOutgoingReviewDecision::Approved => $stage === StandaloneOutgoingReviewStage::SectionHead
                    ? StandaloneOutgoingDraftStatus::AssistantReview
                    : StandaloneOutgoingDraftStatus::AwaitingNumber,
            };
            if ($decision === StandaloneOutgoingReviewDecision::Approved
                && $stage === StandaloneOutgoingReviewStage::Assistant) {
                if ($relatedOutgoing instanceof OutgoingLetter && $relatedOutgoing->status === OutgoingLetterStatus::RevisionRequired) {
                    $relatedOutgoing->status = OutgoingLetterStatus::SekdaReview;
                    $relatedOutgoing->save();
                    $nextStatus = StandaloneOutgoingDraftStatus::SekdaReview;
                }
            }
            $lockedDraft->status = $nextStatus;
            $lockedDraft->save();

            $this->audit->execute(
                $actor,
                AuditAction::StandaloneOutgoingReviewed,
                'standalone_outgoing_draft',
                $lockedDraft->getKey(),
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $lockedDraft->status->value, 'stage' => $stage->value, 'decision' => $decision->value],
                actorPositionAssignment: $assignment,
            );

            return $lockedDraft;
        }, attempts: 3);
    }
}
