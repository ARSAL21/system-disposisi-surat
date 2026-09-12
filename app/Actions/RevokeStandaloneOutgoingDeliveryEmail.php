<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\OutgoingDeliveryMethod;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Exceptions\OutgoingLetterStateConflict;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDelivery;
use App\Models\User;
use App\Services\StandaloneOutgoingDeliveryLinkService;
use App\Services\StandaloneOutgoingLetterLockService;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Support\Facades\DB;

final class RevokeStandaloneOutgoingDeliveryEmail
{
    public function __construct(
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly StandaloneOutgoingLetterLockService $lockService,
        private readonly StandaloneOutgoingDeliveryLinkService $links,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(User $actor, OutgoingLetter $target): void
    {
        DB::transaction(function () use ($actor, $target): void {
            ['draft' => $draft, 'outgoing' => $outgoing] = $this->lockService->lock($target);
            $delivery = OutgoingLetterDelivery::query()->where('outgoing_letter_id', $outgoing->getKey())->lockForUpdate()->first();
            if ($outgoing->origin !== OutgoingLetterOrigin::Standalone || $outgoing->status !== OutgoingLetterStatus::Delivered
                || ! $delivery instanceof OutgoingLetterDelivery || $delivery->method !== OutgoingDeliveryMethod::Email) {
                throw OutgoingLetterStateConflict::stale();
            }
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $staffOwner = (int) $draft->created_by_user_id === (int) $lockedActor->getKey()
                && $this->assignmentResolver->hasStaffAssignmentForUnit($lockedActor, $draft->organizational_unit_id);
            $head = $this->assignmentResolver->hasSectionHeadAssignmentForUnit($lockedActor, $draft->organizational_unit_id);
            if (! $staffOwner && ! $head) {
                throw OutgoingLetterStateConflict::stale();
            }
            $assignment = $this->assignmentResolver->lockDeliveryActorAssignmentForUnit($lockedActor, $draft->organizational_unit_id);
            if (! $this->links->revokeActive($delivery, $lockedActor, $assignment, 'MANUAL_REVOKE')) {
                throw OutgoingLetterStateConflict::stale();
            }
            $this->audit->execute(
                actor: $lockedActor, action: AuditAction::StandaloneOutgoingEmailLinkRevoked,
                subjectType: 'standalone_outgoing_draft', subjectId: $draft->getKey(),
                newValues: ['outgoing_letter_id' => $outgoing->getKey()], actorPositionAssignment: $assignment,
            );
        }, attempts: 3);
    }
}
