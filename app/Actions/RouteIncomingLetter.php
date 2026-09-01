<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\IncomingLetterStatus;
use App\Enums\LetterRouteStatus;
use App\Exceptions\DocumentStorageConflict;
use App\Exceptions\InitialLetterRoutingStateConflict;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\LetterRoute;
use App\Models\User;
use App\Services\DocumentStorageGuard;
use App\Services\ExecutiveRoutingTargetResolver;
use App\Services\LetterRoutingPositionAssignmentResolver;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class RouteIncomingLetter
{
    public function __construct(
        private readonly LetterRoutingPositionAssignmentResolver $positionAssignmentResolver,
        private readonly ExecutiveRoutingTargetResolver $targetResolver,
        private readonly DocumentStorageGuard $storageGuard,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(
        User $actor,
        IncomingLetter $incomingLetter,
        int $targetPositionId,
    ): LetterRoute {
        try {
            return DB::transaction(function () use ($actor, $incomingLetter, $targetPositionId): LetterRoute {
                $lockedLetter = IncomingLetter::query()
                    ->whereKey($incomingLetter->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedLetter->status !== IncomingLetterStatus::Registered) {
                    throw InitialLetterRoutingStateConflict::expectedRegistered($lockedLetter->status);
                }

                if (LetterRoute::query()
                    ->where('incoming_letter_id', $lockedLetter->getKey())
                    ->lockForUpdate()
                    ->exists()) {
                    throw InitialLetterRoutingStateConflict::alreadyExists();
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
                    ->lockRoutingCreatingAssignment($actor);
                [$targetPosition, $targetAssignment] = $this->targetResolver
                    ->lockAvailablePosition($targetPositionId);
                $routedAt = Date::now();

                $letterRoute = new LetterRoute;
                $letterRoute->incoming_letter_id = $lockedLetter->getKey();
                $letterRoute->recipient_position_id = $targetPosition->getKey();
                $letterRoute->routed_by_user_id = $actor->getKey();
                $letterRoute->routed_by_position_assignment_id = $actorAssignment->getKey();
                $letterRoute->status = LetterRouteStatus::Pending;
                $letterRoute->routed_at = $routedAt;
                $letterRoute->completed_at = null;
                $letterRoute->save();

                $lockedLetter->status = IncomingLetterStatus::Routed;
                $lockedLetter->save();

                $this->recordAudit->execute(
                    actor: $actor,
                    action: AuditAction::LetterRouted,
                    subjectType: 'letter_route',
                    subjectId: $letterRoute->getKey(),
                    oldValues: [
                        'letter_status' => IncomingLetterStatus::Registered->value,
                        'route_status' => null,
                    ],
                    newValues: [
                        'letter_status' => IncomingLetterStatus::Routed->value,
                        'route_status' => LetterRouteStatus::Pending->value,
                        'recipient_position_id' => $targetPosition->getKey(),
                    ],
                    metadata: [
                        'incoming_letter_id' => $lockedLetter->getKey(),
                        'recipient_position_assignment_id' => $targetAssignment->getKey(),
                        'document_version_number' => $currentDocument->version_number,
                    ],
                    actorPositionAssignment: $actorAssignment,
                );

                return $letterRoute;
            }, attempts: 3);
        } catch (QueryException $exception) {
            if ($this->isDuplicateRouteViolation($exception)) {
                throw InitialLetterRoutingStateConflict::alreadyExists();
            }

            throw $exception;
        }
    }

    private function isDuplicateRouteViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'letter_routes_incoming_letter_id_unique')
            || str_contains($message, 'letter_routes.incoming_letter_id');
    }
}
