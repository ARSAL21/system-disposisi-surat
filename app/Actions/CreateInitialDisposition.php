<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
use App\Enums\LetterRouteStatus;
use App\Exceptions\DispositionStateConflict;
use App\Exceptions\DocumentStorageConflict;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\InstructionLabel;
use App\Models\LetterDocument;
use App\Models\LetterRoute;
use App\Models\User;
use App\Services\AssistantDispositionTargetResolver;
use App\Services\DispositionPositionAssignmentResolver;
use App\Services\DocumentStorageGuard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateInitialDisposition
{
    public function __construct(
        private readonly DispositionPositionAssignmentResolver $positionAssignmentResolver,
        private readonly AssistantDispositionTargetResolver $targetResolver,
        private readonly DocumentStorageGuard $storageGuard,
        private readonly RecordAudit $recordAudit,
    ) {}

    /**
     * @param  list<int>  $instructionLabelIds
     */
    public function execute(
        User $actor,
        LetterRoute $letterRoute,
        int $recipientPositionId,
        array $instructionLabelIds,
        ?string $instructionNote,
    ): Disposition {
        try {
            return DB::transaction(function () use (
                $actor,
                $letterRoute,
                $recipientPositionId,
                $instructionLabelIds,
                $instructionNote,
            ): Disposition {
                $lockedActor = User::query()
                    ->whereKey($actor->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $lockedRoute = LetterRoute::query()
                    ->whereKey($letterRoute->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedRoute->status !== LetterRouteStatus::Pending) {
                    throw DispositionStateConflict::staleSource();
                }

                $lockedLetter = IncomingLetter::query()
                    ->whereKey($lockedRoute->incoming_letter_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedLetter->status !== IncomingLetterStatus::Routed) {
                    throw DispositionStateConflict::staleSource();
                }

                if (Disposition::query()
                    ->where('source_route_id', $lockedRoute->getKey())
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
                    ->lockExecutiveAssignmentForPosition($lockedActor, $lockedRoute->recipient_position_id);
                [$recipientPosition, $recipientAssignment] = $this->targetResolver
                    ->lockAvailablePosition(
                        $recipientPositionId,
                        $actorAssignment->position_id,
                        (int) $lockedActor->getKey(),
                    );
                $instructionLabels = $this->lockActiveInstructionLabels($instructionLabelIds);
                $now = Date::now();

                $disposition = new Disposition;
                $disposition->incoming_letter_id = $lockedLetter->getKey();
                $disposition->source_route_id = $lockedRoute->getKey();
                $disposition->parent_recipient_id = null;
                $disposition->created_by_user_id = $lockedActor->getKey();
                $disposition->created_by_position_assignment_id = $actorAssignment->getKey();
                $disposition->instruction_note = $instructionNote;
                $disposition->created_at = $now;
                $disposition->save();

                $disposition->instructionLabels()->attach($instructionLabels->modelKeys());

                $recipient = new DispositionRecipient;
                $recipient->disposition_id = $disposition->getKey();
                $recipient->recipient_position_id = $recipientPosition->getKey();
                $recipient->status = DispositionRecipientStatus::Pending;
                $recipient->received_at = $now;
                $recipient->started_at = null;
                $recipient->completed_at = null;
                $recipient->completed_by_user_id = null;
                $recipient->completed_by_position_assignment_id = null;
                $recipient->completion_note = null;
                $recipient->save();

                $lockedRoute->status = LetterRouteStatus::Completed;
                $lockedRoute->completed_at = $now;
                $lockedRoute->save();

                $lockedLetter->status = IncomingLetterStatus::InProgress;
                $lockedLetter->save();

                $this->recordAudit->execute(
                    actor: $lockedActor,
                    action: AuditAction::DispositionCreated,
                    subjectType: 'disposition',
                    subjectId: $disposition->getKey(),
                    newValues: [
                        'letter_status' => IncomingLetterStatus::InProgress->value,
                        'route_status' => LetterRouteStatus::Completed->value,
                        'recipient_status' => DispositionRecipientStatus::Pending->value,
                        'recipient_position_id' => $recipientPosition->getKey(),
                        'instruction_label_codes' => $instructionLabels->pluck('code')->values()->all(),
                    ],
                    metadata: [
                        'incoming_letter_id' => $lockedLetter->getKey(),
                        'source_route_id' => $lockedRoute->getKey(),
                        'recipient_id' => $recipient->getKey(),
                        'recipient_position_assignment_id' => $recipientAssignment->getKey(),
                        'document_version_number' => $currentDocument->version_number,
                    ],
                    actorPositionAssignment: $actorAssignment,
                );

                return $disposition;
            }, attempts: 3);
        } catch (QueryException $exception) {
            if ($this->isDuplicateSourceRouteViolation($exception)) {
                throw DispositionStateConflict::alreadyExists();
            }

            throw $exception;
        }
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

    private function isDuplicateSourceRouteViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'dispositions_source_route_id_unique')
            || str_contains($message, 'dispositions.source_route_id');
    }
}
