<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\LetterResponseDossierStatus;
use App\Enums\OutgoingDeliveryMethod;
use App\Enums\OutgoingLetterReviewDecision;
use App\Enums\OutgoingLetterStatus;
use App\Enums\SubmissionSource;
use App\Exceptions\OutgoingLetterStateConflict;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterDelivery;
use App\Models\OutgoingLetterDocumentVersion;
use App\Models\User;
use App\Notifications\OfficialResponseAvailable;
use App\Services\LetterResponseFulfillmentService;
use App\Services\OutgoingLetterDocumentStorage;
use App\Services\OutgoingLetterLockService;
use App\Services\OutgoingLetterPositionAssignmentResolver;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DeliverOutgoingLetter
{
    public function __construct(
        private readonly OutgoingLetterLockService $lockService,
        private readonly OutgoingLetterPositionAssignmentResolver $assignmentResolver,
        private readonly OutgoingLetterDocumentStorage $storage,
        private readonly LetterResponseFulfillmentService $fulfillment,
        private readonly RecordAudit $recordAudit,
    ) {}

    /** @param array{delivery_method?: string|null, recipient_name?: string|null, delivered_at?: string|null, tracking_number?: string|null, delivery_note?: string|null} $data */
    public function execute(User $actor, OutgoingLetter $target, array $data): OutgoingLetterDelivery
    {
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
}
