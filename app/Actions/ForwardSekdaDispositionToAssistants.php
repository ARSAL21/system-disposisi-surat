<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
use App\Exceptions\DispositionStateConflict;
use App\Exceptions\DocumentStorageConflict;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\InstructionLabel;
use App\Models\LetterDocument;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use App\Services\AssistantDispositionTargetResolver;
use App\Services\DispositionPositionAssignmentResolver;
use App\Services\DocumentStorageGuard;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ForwardSekdaDispositionToAssistants
{
    public function __construct(
        private readonly DispositionPositionAssignmentResolver $positionAssignmentResolver,
        private readonly AssistantDispositionTargetResolver $targetResolver,
        private readonly DocumentStorageGuard $storageGuard,
        private readonly RecordAudit $recordAudit,
    ) {}

    /**
     * @param  list<int>  $recipientPositionIds
     * @param  list<int>  $instructionLabelIds
     */
    public function execute(
        User $actor,
        DispositionRecipient $parentRecipient,
        array $recipientPositionIds,
        array $instructionLabelIds,
        ?string $instructionNote,
    ): Disposition {
        return DB::transaction(function () use ($actor, $parentRecipient, $recipientPositionIds, $instructionLabelIds, $instructionNote): Disposition {
            $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
            $lockedParent = DispositionRecipient::query()->whereKey($parentRecipient->getKey())->lockForUpdate()->firstOrFail();
            if ($lockedParent->status !== DispositionRecipientStatus::Pending) {
                throw DispositionStateConflict::staleSource();
            }

            $parentDisposition = Disposition::query()->whereKey($lockedParent->disposition_id)->lockForUpdate()->firstOrFail();
            $lockedLetter = IncomingLetter::query()->whereKey($parentDisposition->incoming_letter_id)->lockForUpdate()->firstOrFail();
            if ($lockedLetter->status !== IncomingLetterStatus::InProgress
                || ! $this->isMayorToSekdaRecipient($lockedParent, $parentDisposition)
                || Disposition::query()->where('parent_recipient_id', $lockedParent->getKey())->lockForUpdate()->exists()) {
                throw DispositionStateConflict::staleSource();
            }

            $currentDocument = LetterDocument::query()
                ->where('incoming_letter_id', $lockedLetter->getKey())
                ->orderByDesc('version_number')->orderByDesc('id')->lockForUpdate()->first();
            if (! $currentDocument instanceof LetterDocument) {
                throw DocumentStorageConflict::invalidMetadata();
            }

            $this->storageGuard->validateOfficialLetterDocument($lockedLetter, $currentDocument);
            $actorAssignment = $this->positionAssignmentResolver
                ->lockRegionalSecretaryAssignmentForPosition($lockedActor, $lockedParent->recipient_position_id);
            $targets = $this->targetResolver->lockAvailablePositions(
                $recipientPositionIds,
                $actorAssignment->position_id,
                (int) $lockedActor->getKey(),
            );
            $labels = $this->lockActiveInstructionLabels($instructionLabelIds);
            $now = Date::now();

            $disposition = new Disposition;
            $disposition->incoming_letter_id = $lockedLetter->getKey();
            $disposition->source_route_id = null;
            $disposition->parent_recipient_id = $lockedParent->getKey();
            $disposition->created_by_user_id = $lockedActor->getKey();
            $disposition->created_by_position_assignment_id = $actorAssignment->getKey();
            $disposition->instruction_note = $instructionNote;
            $disposition->created_at = $now;
            $disposition->save();
            $disposition->instructionLabels()->attach($labels->modelKeys());
            $recipients = $this->createRecipients($disposition, $targets, $now);

            $lockedParent->status = DispositionRecipientStatus::Completed;
            $lockedParent->completed_at = $now;
            $lockedParent->completed_by_user_id = $lockedActor->getKey();
            $lockedParent->completed_by_position_assignment_id = $actorAssignment->getKey();
            $lockedParent->completion_note = null;
            $lockedParent->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::DispositionCreated,
                subjectType: 'disposition',
                subjectId: $disposition->getKey(),
                newValues: [
                    'letter_status' => IncomingLetterStatus::InProgress->value,
                    'parent_recipient_status' => DispositionRecipientStatus::Completed->value,
                    'recipient_status' => DispositionRecipientStatus::Pending->value,
                    'recipient_position_ids' => $targets->map(static fn (array $target): int => (int) $target[0]->getKey())->values()->all(),
                    'instruction_label_codes' => $labels->pluck('code')->values()->all(),
                ],
                metadata: [
                    'incoming_letter_id' => $lockedLetter->getKey(),
                    'parent_recipient_id' => $lockedParent->getKey(),
                    'parent_disposition_id' => $parentDisposition->getKey(),
                    'recipient_ids' => $recipients->modelKeys(),
                    'recipient_position_assignment_ids' => $targets->map(static fn (array $target): int => (int) $target[1]->getKey())->values()->all(),
                    'document_version_number' => $currentDocument->version_number,
                    'hierarchy' => [
                        'rule' => 'REGIONAL_SECRETARY_TO_ASSISTANT',
                        'source_position_code' => OrganizationCatalog::REGIONAL_SECRETARY_POSITION,
                    ],
                ],
                actorPositionAssignment: $actorAssignment,
            );

            return $disposition;
        }, attempts: 3);
    }

    /**
     * @param  SupportCollection<int, array{Position, PositionAssignment}>  $targets
     * @return Collection<int, DispositionRecipient>
     */
    private function createRecipients(Disposition $disposition, SupportCollection $targets, CarbonInterface $receivedAt): Collection
    {
        $recipients = new Collection;
        foreach ($targets as [$position]) {
            $recipient = new DispositionRecipient;
            $recipient->disposition_id = $disposition->getKey();
            $recipient->recipient_position_id = $position->getKey();
            $recipient->status = DispositionRecipientStatus::Pending;
            $recipient->received_at = $receivedAt;
            $recipient->started_at = null;
            $recipient->completed_at = null;
            $recipient->completed_by_user_id = null;
            $recipient->completed_by_position_assignment_id = null;
            $recipient->completion_note = null;
            $recipient->save();
            $recipients->push($recipient);
        }

        return $recipients;
    }

    /**
     * @param  list<int>  $instructionLabelIds
     * @return Collection<int, InstructionLabel>
     */
    private function lockActiveInstructionLabels(array $instructionLabelIds): Collection
    {
        $ids = array_values(array_unique($instructionLabelIds));
        if (count($ids) < 1 || count($ids) > 10 || count($ids) !== count($instructionLabelIds)) {
            throw ValidationException::withMessages(['instruction_label_ids' => 'Pilih 1 sampai 10 instruksi aktif tanpa duplikasi.']);
        }

        $labels = InstructionLabel::query()->whereIn('id', $ids)->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->lockForUpdate()->get();
        if ($labels->count() !== count($ids)) {
            throw ValidationException::withMessages(['instruction_label_ids' => 'Satu atau lebih instruksi tidak aktif atau tidak tersedia.']);
        }

        return $labels;
    }

    private function isMayorToSekdaRecipient(DispositionRecipient $recipient, Disposition $disposition): bool
    {
        return $recipient->recipientPosition()
            ->where('code', OrganizationCatalog::REGIONAL_SECRETARY_POSITION)
            ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                ->where('code', OrganizationCatalog::REGIONAL_SECRETARY_LEVEL)
                ->where('is_active', true))
            ->exists()
            && $disposition->sourceRoute()
                ->whereHas('recipientPosition', fn (Builder $position): Builder => $position
                    ->where('code', OrganizationCatalog::MAYOR_POSITION)
                    ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                        ->where('code', OrganizationCatalog::MAYOR_LEVEL)
                        ->where('is_active', true)))
                ->exists();
    }
}
