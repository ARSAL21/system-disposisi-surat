<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
use App\Enums\InitialLetterRoutePath;
use App\Enums\LetterRouteStatus;
use App\Exceptions\DispositionStateConflict;
use App\Exceptions\DocumentStorageConflict;
use App\ExpertConsultations\ExpertConsultationForwardingGuard;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\InstructionLabel;
use App\Models\LetterDocument;
use App\Models\LetterRoute;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use App\Services\DispositionPositionAssignmentResolver;
use App\Services\DocumentStorageGuard;
use App\Services\ExecutiveRoutingTargetResolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateMayorDispositionToSekda
{
    public function __construct(
        private readonly DispositionPositionAssignmentResolver $positionAssignmentResolver,
        private readonly ExecutiveRoutingTargetResolver $targetResolver,
        private readonly DocumentStorageGuard $storageGuard,
        private readonly ExpertConsultationForwardingGuard $expertConsultationForwardingGuard,
        private readonly RecordAudit $recordAudit,
    ) {}

    /** @param list<int> $instructionLabelIds */
    public function execute(
        User $actor,
        LetterRoute $letterRoute,
        array $instructionLabelIds,
        ?string $instructionNote,
    ): Disposition {
        try {
            return DB::transaction(function () use ($actor, $letterRoute, $instructionLabelIds, $instructionNote): Disposition {
                $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
                $lockedRoute = LetterRoute::query()->whereKey($letterRoute->getKey())->lockForUpdate()->firstOrFail();

                if ($lockedRoute->status !== LetterRouteStatus::Pending || ! $this->routeTargetsMayor($lockedRoute)) {
                    throw DispositionStateConflict::staleSource();
                }

                $lockedLetter = IncomingLetter::query()->whereKey($lockedRoute->incoming_letter_id)->lockForUpdate()->firstOrFail();
                if ($lockedLetter->status !== IncomingLetterStatus::Routed
                    || Disposition::query()->where('source_route_id', $lockedRoute->getKey())->lockForUpdate()->exists()) {
                    throw DispositionStateConflict::staleSource();
                }

                $this->expertConsultationForwardingGuard->ensureNoPendingForLockedRoute($lockedRoute);

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
                    ->lockMayorAssignmentForPosition($lockedActor, $lockedRoute->recipient_position_id);
                [$sekdaPosition, $sekdaAssignment] = $this->targetResolver
                    ->lockAvailablePosition(InitialLetterRoutePath::DirectToSekda);
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
                $recipient->recipient_position_id = $sekdaPosition->getKey();
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
                        'recipient_position_ids' => [(int) $sekdaPosition->getKey()],
                        'instruction_label_codes' => $instructionLabels->pluck('code')->values()->all(),
                    ],
                    metadata: [
                        'incoming_letter_id' => $lockedLetter->getKey(),
                        'source_route_id' => $lockedRoute->getKey(),
                        'recipient_ids' => [$recipient->getKey()],
                        'recipient_position_assignment_ids' => [$sekdaAssignment->getKey()],
                        'document_version_number' => $currentDocument->version_number,
                        'hierarchy' => [
                            'rule' => 'MAYOR_TO_REGIONAL_SECRETARY',
                            'source_position_code' => OrganizationCatalog::MAYOR_POSITION,
                            'recipient_position_code' => OrganizationCatalog::REGIONAL_SECRETARY_POSITION,
                        ],
                    ],
                    actorPositionAssignment: $actorAssignment,
                );

                return $disposition;
            }, attempts: 3);
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'dispositions_source_route_id_unique')) {
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
            throw ValidationException::withMessages(['instruction_label_ids' => 'Pilih 1 sampai 10 instruksi aktif tanpa duplikasi.']);
        }

        $labels = InstructionLabel::query()->whereIn('id', $ids)->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->lockForUpdate()->get();
        if ($labels->count() !== count($ids)) {
            throw ValidationException::withMessages(['instruction_label_ids' => 'Satu atau lebih instruksi tidak aktif atau tidak tersedia.']);
        }

        return $labels;
    }

    private function routeTargetsMayor(LetterRoute $route): bool
    {
        return $route->recipientPosition()
            ->where('code', OrganizationCatalog::MAYOR_POSITION)
            ->whereHas('positionLevel', fn ($level) => $level->where('code', OrganizationCatalog::MAYOR_LEVEL)->where('is_active', true))
            ->exists();
    }
}
