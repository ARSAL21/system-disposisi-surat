<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\LetterResponseDossierStatus;
use App\Enums\OutgoingDeliveryMethod;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterReviewDecision;
use App\Enums\OutgoingLetterStatus;
use App\Enums\SubmissionSource;
use App\Exceptions\OutgoingLetterStateConflict;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDelivery;
use App\Models\OutgoingLetterDocumentVersion;
use App\Models\OutgoingLetterInternalCopyNotification;
use App\Models\PositionAssignment;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use App\Notifications\OfficialResponseAvailable;
use App\Notifications\StandaloneOutgoingDeliveryLinkAvailable;
use App\Notifications\StandaloneOutgoingInternalCopyAvailable;
use App\OutgoingLetters\IssuedOutgoingDeliveryLink;
use App\Services\LetterResponseFulfillmentService;
use App\Services\OutgoingLetterDocumentStorage;
use App\Services\OutgoingLetterLockService;
use App\Services\OutgoingLetterPositionAssignmentResolver;
use App\Services\StandaloneOutgoingDeliveryLinkService;
use App\Services\StandaloneOutgoingFinalDocumentStorage;
use App\Services\StandaloneOutgoingLetterLockService;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class DeliverOutgoingLetter
{
    public function __construct(
        private readonly OutgoingLetterLockService $lockService,
        private readonly OutgoingLetterPositionAssignmentResolver $assignmentResolver,
        private readonly OutgoingLetterDocumentStorage $storage,
        private readonly LetterResponseFulfillmentService $fulfillment,
        private readonly RecordAudit $recordAudit,
        private readonly StandaloneOutgoingFinalDocumentStorage $standaloneStorage,
        private readonly StandaloneOutgoingLetterLockService $standaloneLockService,
        private readonly StandaloneOutgoingPositionAssignmentResolver $standaloneAssignmentResolver,
        private readonly StandaloneOutgoingDeliveryLinkService $deliveryLinks,
    ) {}

    /** @param array{delivery_method?: string|null, recipient_name?: string|null, delivered_at?: string|null, tracking_number?: string|null, delivery_note?: string|null} $data */
    public function execute(User $actor, OutgoingLetter $target, array $data): OutgoingLetterDelivery
    {
        if ($target->origin === OutgoingLetterOrigin::Standalone) {
            return $this->deliverStandalone($actor, $target, $data);
        }

        $delivery = DB::transaction(function () use ($actor, $target, $data): OutgoingLetterDelivery {
            $context = $this->lockService->lock($target);
            $outgoing = $context['outgoing'];
            $submission = $context['letter']->submission()->with('submitter')->firstOrFail();
            $version = OutgoingLetterDocumentVersion::query()
                ->where('outgoing_letter_id', $outgoing->getKey())
                ->with('review')
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->first();

            if ($context['dossier']->status !== LetterResponseDossierStatus::Finalized
                || $outgoing->status !== OutgoingLetterStatus::AdminVerified
                || ! $version instanceof OutgoingLetterDocumentVersion
                || $version->review?->decision !== OutgoingLetterReviewDecision::Verified
                || $outgoing->delivery()->exists()) {
                throw OutgoingLetterStateConflict::stale();
            }

            $this->storage->validate($outgoing, $version);
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $assignment = $this->assignmentResolver->lockGeneralAffairsOfficer($lockedActor);
            $online = $submission->source === SubmissionSource::Online;
            $method = $online
                ? OutgoingDeliveryMethod::Portal
                : OutgoingDeliveryMethod::from((string) ($data['delivery_method'] ?? ''));
            $deliveredAt = $online ? Date::now() : Date::parse((string) ($data['delivered_at'] ?? ''));

            $delivery = new OutgoingLetterDelivery;
            $delivery->outgoing_letter_id = $outgoing->getKey();
            $delivery->method = $method;
            $delivery->recipient_name = $online ? null : trim((string) ($data['recipient_name'] ?? ''));
            $delivery->tracking_number = $this->nullableTrim($data['tracking_number'] ?? null);
            $delivery->note = $this->nullableTrim($data['delivery_note'] ?? null);
            $delivery->delivered_by_user_id = $lockedActor->getKey();
            $delivery->delivered_by_position_assignment_id = $assignment->getKey();
            $delivery->delivered_at = $deliveredAt;
            $delivery->save();

            $outgoing->status = OutgoingLetterStatus::Delivered;
            $outgoing->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::OutgoingLetterDelivered,
                subjectType: 'incoming_letter',
                subjectId: $context['letter']->getKey(),
                oldValues: ['status' => OutgoingLetterStatus::AdminVerified->value],
                newValues: [
                    'outgoing_letter_id' => $outgoing->getKey(),
                    'status' => OutgoingLetterStatus::Delivered->value,
                    'delivery_method' => $method->value,
                ],
                actorPositionAssignment: $assignment,
            );
            $this->fulfillment->fulfillWhenComplete(
                $context['dossier'],
                $context['mandates'],
                $lockedActor,
                $assignment,
            );

            return $delivery;
        }, attempts: 3);

        $published = $delivery->outgoingLetter()
            ->with('incomingLetter.submission.submitter')
            ->firstOrFail();
        $submission = $published->incomingLetter?->submission;

        if ($submission?->source === SubmissionSource::Online && $submission->submitter instanceof User) {

            try {
                $submission->submitter->notify(new OfficialResponseAvailable($published, $submission));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $delivery;
    }

    private function nullableTrim(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    /** @param array{delivery_method?: string|null, recipient_name?: string|null, delivered_at?: string|null, tracking_number?: string|null, delivery_note?: string|null} $data */
    private function deliverStandalone(User $actor, OutgoingLetter $target, array $data): OutgoingLetterDelivery
    {
        /** @var IssuedOutgoingDeliveryLink|null $issuedLink */
        $issuedLink = null;
        /** @var list<User> $copyRecipients */
        $copyRecipients = [];

        $delivery = DB::transaction(function () use ($actor, $target, $data, &$issuedLink, &$copyRecipients): OutgoingLetterDelivery {
            ['draft' => $draft, 'outgoing' => $outgoing] = $this->standaloneLockService->lock($target);
            $version = OutgoingLetterDocumentVersion::query()
                ->where('outgoing_letter_id', $outgoing->getKey())
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->first();
            if ($outgoing->origin !== OutgoingLetterOrigin::Standalone
                || $outgoing->status !== OutgoingLetterStatus::ReadyForDelivery
                || ! $version instanceof OutgoingLetterDocumentVersion
                || $outgoing->delivery()->lockForUpdate()->exists()) {
                throw OutgoingLetterStateConflict::stale();
            }

            $this->standaloneStorage->validate($outgoing, $version);
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $isStaffOwner = (int) $draft->created_by_user_id === (int) $lockedActor->getKey()
                && $this->standaloneAssignmentResolver->hasStaffAssignmentForUnit($lockedActor, $draft->organizational_unit_id);
            $isSectionHead = $this->standaloneAssignmentResolver->hasSectionHeadAssignmentForUnit($lockedActor, $draft->organizational_unit_id);
            if (! $isStaffOwner && ! $isSectionHead) {
                throw OutgoingLetterStateConflict::stale();
            }
            $assignment = $this->standaloneAssignmentResolver->lockDeliveryActorAssignmentForUnit($lockedActor, $draft->organizational_unit_id);
            $method = OutgoingDeliveryMethod::from((string) ($data['delivery_method'] ?? ''));
            $isEmail = $method === OutgoingDeliveryMethod::Email;
            $delivery = new OutgoingLetterDelivery;
            $delivery->outgoing_letter_id = $outgoing->getKey();
            $delivery->method = $method;
            $delivery->recipient_name = $isEmail ? $draft->recipient_name : trim((string) ($data['recipient_name'] ?? ''));
            $delivery->tracking_number = $this->nullableTrim($data['tracking_number'] ?? null);
            $delivery->note = $this->nullableTrim($data['delivery_note'] ?? null);
            $delivery->delivered_by_user_id = $lockedActor->getKey();
            $delivery->delivered_by_position_assignment_id = $assignment->getKey();
            $delivery->delivered_at = $isEmail ? Date::now() : Date::parse((string) ($data['delivered_at'] ?? ''));
            $delivery->save();
            $outgoing->status = OutgoingLetterStatus::Delivered;
            $outgoing->save();

            if ($isEmail) {
                $email = $draft->recipient_email;
                if (! is_string($email) || $email === '') {
                    throw OutgoingLetterStateConflict::stale();
                }
                $issuedLink = $this->deliveryLinks->issue($delivery, $email, $lockedActor, $assignment);
            }

            $copyRecipients = $this->recordInternalCopyNotifications($delivery, $draft, $outgoing);

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::StandaloneOutgoingDelivered,
                subjectType: 'standalone_outgoing_draft',
                subjectId: $draft->getKey(),
                oldValues: ['status' => OutgoingLetterStatus::ReadyForDelivery->value],
                newValues: [
                    'outgoing_letter_id' => $outgoing->getKey(),
                    'status' => OutgoingLetterStatus::Delivered->value,
                    'delivery_method' => $method->value,
                ],
                actorPositionAssignment: $assignment,
            );

            return $delivery;
        }, attempts: 3);

        $published = $delivery->outgoingLetter()
            ->with('standaloneDraft.organizationalUnit')
            ->firstOrFail();

        if ($issuedLink instanceof IssuedOutgoingDeliveryLink) {
            try {
                Notification::route('mail', $issuedLink->link->recipient_email)
                    ->notify(new StandaloneOutgoingDeliveryLinkAvailable($published, $issuedLink->plainToken));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        foreach ($copyRecipients as $copyRecipient) {
            try {
                $copyRecipient->notify(new StandaloneOutgoingInternalCopyAvailable($published));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $delivery;
    }

    /** @return list<User> */
    private function recordInternalCopyNotifications(OutgoingLetterDelivery $delivery, StandaloneOutgoingDraft $draft, OutgoingLetter $outgoing): array
    {
        $positionIds = $draft->copyRecipients()->lockForUpdate()->pluck('position_id')->map(static fn (mixed $id): int => (int) $id)->all();
        if ($positionIds === []) {
            return [];
        }

        $assignments = PositionAssignment::query()
            ->whereIn('position_id', $positionIds)
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->with(['user', 'position'])
            ->orderBy('position_id')
            ->lockForUpdate()
            ->get();
        $recipients = [];
        foreach ($assignments as $assignment) {
            if ($assignment->position_id < 1) {
                continue;
            }

            $recipient = $assignment->user;
            if (! $recipient->isInternalAccount() || ! $recipient->is_active || ! $recipient->hasVerifiedEmail()
                || ! Gate::forUser($recipient)->allows('view', $outgoing)) {
                continue;
            }
            $notice = new OutgoingLetterInternalCopyNotification;
            $notice->outgoing_letter_delivery_id = $delivery->getKey();
            $notice->position_id = $assignment->position_id;
            $notice->recipient_user_id = $recipient->getKey();
            $notice->recipient_position_assignment_id = $assignment->getKey();
            $notice->notified_at = Date::now();
            $notice->save();
            $recipients[] = $recipient;
        }

        return $recipients;
    }
}
