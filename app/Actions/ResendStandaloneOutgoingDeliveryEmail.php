<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\OutgoingDeliveryMethod;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Exceptions\OutgoingLetterStateConflict;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDelivery;
use App\Models\PositionAssignment;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use App\Notifications\StandaloneOutgoingDeliveryLinkAvailable;
use App\OutgoingLetters\IssuedOutgoingDeliveryLink;
use App\Services\StandaloneOutgoingDeliveryLinkService;
use App\Services\StandaloneOutgoingLetterLockService;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class ResendStandaloneOutgoingDeliveryEmail
{
    public function __construct(
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly StandaloneOutgoingLetterLockService $lockService,
        private readonly StandaloneOutgoingDeliveryLinkService $links,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(User $actor, OutgoingLetter $target): void
    {
        /** @var IssuedOutgoingDeliveryLink|null $issued */
        $issued = null;
        /** @var OutgoingLetter|null $published */
        $published = DB::transaction(function () use ($actor, $target, &$issued): OutgoingLetter {
            ['draft' => $draft, 'outgoing' => $outgoing] = $this->lockService->lock($target);
            $delivery = OutgoingLetterDelivery::query()->where('outgoing_letter_id', $outgoing->getKey())->lockForUpdate()->first();
            if ($outgoing->origin !== OutgoingLetterOrigin::Standalone || $outgoing->status !== OutgoingLetterStatus::Delivered
                || ! $delivery instanceof OutgoingLetterDelivery || $delivery->method !== OutgoingDeliveryMethod::Email
                || ! is_string($draft->recipient_email) || $draft->recipient_email === '') {
                throw OutgoingLetterStateConflict::stale();
            }
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $assignment = $this->lockActor($lockedActor, $draft);
            $issued = $this->links->issue($delivery, $draft->recipient_email, $lockedActor, $assignment);
            $this->audit->execute(
                actor: $lockedActor, action: AuditAction::StandaloneOutgoingEmailLinkIssued,
                subjectType: 'standalone_outgoing_draft', subjectId: $draft->getKey(),
                newValues: ['outgoing_letter_id' => $outgoing->getKey(), 'delivery_link_id' => $issued->link->getKey()],
                actorPositionAssignment: $assignment,
            );

            return $outgoing;
        }, attempts: 3);

        if ($issued instanceof IssuedOutgoingDeliveryLink) {
            try {
                $published->loadMissing('standaloneDraft.organizationalUnit');
                Notification::route('mail', $issued->link->recipient_email)
                    ->notify(new StandaloneOutgoingDeliveryLinkAvailable($published, $issued->plainToken));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    private function lockActor(User $actor, StandaloneOutgoingDraft $draft): PositionAssignment
    {
        $staffOwner = (int) $draft->created_by_user_id === (int) $actor->getKey()
            && $this->assignmentResolver->hasStaffAssignmentForUnit($actor, $draft->organizational_unit_id);
        $head = $this->assignmentResolver->hasSectionHeadAssignmentForUnit($actor, $draft->organizational_unit_id);
        if (! $staffOwner && ! $head) {
            throw OutgoingLetterStateConflict::stale();
        }

        return $this->assignmentResolver->lockDeliveryActorAssignmentForUnit($actor, $draft->organizational_unit_id);
    }
}
