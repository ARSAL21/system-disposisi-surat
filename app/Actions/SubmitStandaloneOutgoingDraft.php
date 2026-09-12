<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use App\Services\StandaloneOutgoingDocumentStorage;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Support\Facades\DB;

final class SubmitStandaloneOutgoingDraft
{
    public function __construct(
        private readonly StandaloneOutgoingDocumentStorage $storage,
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(User $actor, StandaloneOutgoingDraft $draft): StandaloneOutgoingDraft
    {
        return DB::transaction(function () use ($actor, $draft): StandaloneOutgoingDraft {
            $lockedDraft = StandaloneOutgoingDraft::query()->whereKey($draft->getKey())->lockForUpdate()->firstOrFail();
            if (! in_array($lockedDraft->status, [StandaloneOutgoingDraftStatus::Draft, StandaloneOutgoingDraftStatus::RevisionRequired], true)
                || (int) $lockedDraft->created_by_user_id !== (int) $actor->getKey()) {
                throw StandaloneOutgoingStateConflict::stale();
            }

            $version = StandaloneOutgoingDocumentVersion::query()
                ->where('standalone_outgoing_draft_id', $lockedDraft->getKey())
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->firstOrFail();
            $this->storage->validate($lockedDraft, $version);

            $assignment = $this->assignmentResolver->lockDeliveryActorAssignmentForUnit($actor, $lockedDraft->organizational_unit_id);

            $oldStatus = $lockedDraft->status->value;
            $lockedDraft->status = StandaloneOutgoingDraftStatus::SectionReview;
            $lockedDraft->submitted_at ??= now();
            $lockedDraft->save();

            $this->audit->execute(
                $actor,
                AuditAction::StandaloneOutgoingSubmitted,
                'standalone_outgoing_draft',
                $lockedDraft->getKey(),
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $lockedDraft->status->value],
                actorPositionAssignment: $assignment,
            );

            return $lockedDraft;
        }, attempts: 3);
    }
}
