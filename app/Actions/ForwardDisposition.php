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
use App\Services\DispositionPositionAssignmentResolver;
use App\Services\DocumentStorageGuard;
use App\Services\SectionHeadDispositionTargetResolver;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ForwardDisposition
{
    public function __construct(
        private readonly DispositionPositionAssignmentResolver $positionAssignmentResolver,
        private readonly SectionHeadDispositionTargetResolver $targetResolver,
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
        return DB::transaction(function () use (
            $actor,
            $parentRecipient,
            $recipientPositionIds,
            $instructionLabelIds,
            $instructionNote,
        ): Disposition {
            $lockedActor = User::query()
                ->whereKey($actor->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedParentRecipient = DispositionRecipient::query()
                ->whereKey($parentRecipient->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedParentRecipient->status !== DispositionRecipientStatus::Pending) {
                throw DispositionStateConflict::staleSource();
            }

            $parentDisposition = Disposition::query()
                ->whereKey($lockedParentRecipient->disposition_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedLetter = IncomingLetter::query()
                ->whereKey($parentDisposition->incoming_letter_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLetter->status !== IncomingLetterStatus::InProgress) {
                throw DispositionStateConflict::staleSource();
            }

            if (Disposition::query()
                ->where('parent_recipient_id', $lockedParentRecipient->getKey())
                ->lockForUpdate()
                ->exists()) {
                throw DispositionStateConflict::alreadyExists();
            }

            $currentDocument = LetterDocument::query()
                ->where('incoming_letter_id', $lockedLetter->getKey())
                ->orderByDesc('version_number')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $currentDocument instanceof LetterDocument) {
                throw DocumentStorageConflict::invalidMetadata();
            }

            $this->storageGuard->validateOfficialLetterDocument($lockedLetter, $currentDocument);
            $actorAssignment = $this->positionAssignmentResolver
                ->lockAssistantAssignmentForPosition($lockedActor, $lockedParentRecipient->recipient_position_id);
            $recipientTargets = $this->targetResolver
                ->lockAvailablePositions($recipientPositionIds, (int) $lockedActor->getKey());
            $instructionLabels = $this->lockActiveInstructionLabels($instructionLabelIds);
            $now = Date::now();

            $disposition = new Disposition;
            $disposition->incoming_letter_id = $lockedLetter->getKey();
            $disposition->source_route_id = null;
            $disposition->parent_recipient_id = $lockedParentRecipient->getKey();
            $disposition->created_by_user_id = $lockedActor->getKey();
            $disposition->created_by_position_assignment_id = $actorAssignment->getKey();
            $disposition->instruction_note = $instructionNote;
            $disposition->created_at = $now;
            $disposition->save();

            $disposition->instructionLabels()->attach($instructionLabels->modelKeys());
            $recipientRecords = $this->createRecipients($disposition, $recipientTargets, $now);

            $lockedParentRecipient->status = DispositionRecipientStatus::Completed;
            $lockedParentRecipient->completed_at = $now;
            $lockedParentRecipient->completed_by_user_id = $lockedActor->getKey();
            $lockedParentRecipient->completed_by_position_assignment_id = $actorAssignment->getKey();
            $lockedParentRecipient->completion_note = null;
            $lockedParentRecipient->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::DispositionCreated,
                subjectType: 'disposition',
                subjectId: $disposition->getKey(),
                newValues: [
                    'letter_status' => IncomingLetterStatus::InProgress->value,
                    'parent_recipient_status' => DispositionRecipientStatus::Completed->value,
                    'recipient_status' => DispositionRecipientStatus::Pending->value,
                    'recipient_position_ids' => $recipientTargets
                        ->map(static fn (array $target): int => (int) $target[0]->getKey())
                        ->values()
                        ->all(),
                    'instruction_label_codes' => $instructionLabels->pluck('code')->values()->all(),
                ],
                metadata: [
                    'incoming_letter_id' => $lockedLetter->getKey(),
                    'parent_recipient_id' => $lockedParentRecipient->getKey(),
                    'parent_disposition_id' => $parentDisposition->getKey(),
                    'recipient_ids' => $recipientRecords->modelKeys(),
                    'recipient_position_assignment_ids' => $recipientTargets
                        ->map(static fn (array $target): int => (int) $target[1]->getKey())
                        ->values()
                        ->all(),
                    'document_version_number' => $currentDocument->version_number,
                ],
                actorPositionAssignment: $actorAssignment,
            );

            return $disposition;
        }, attempts: 3);
    }

    /**
     * @param  SupportCollection<int, array{Position, PositionAssignment}>  $recipientTargets
     * @return Collection<int, DispositionRecipient>
     */
    private function createRecipients(
        Disposition $disposition,
        SupportCollection $recipientTargets,
        CarbonInterface $receivedAt,
    ): Collection {
        $recipients = new Collection;

        foreach ($recipientTargets as [$position]) {
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
            throw ValidationException::withMessages([
                'instruction_label_ids' => 'Pilih 1 sampai 10 instruksi aktif tanpa duplikasi.',
            ]);
        }

        $labels = InstructionLabel::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($labels->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'instruction_label_ids' => 'Satu atau lebih instruksi tidak aktif atau tidak tersedia.',
            ]);
        }

        return $labels;
    }
}
