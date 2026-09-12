<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\DispositionRecipient;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SectionHeadDispositionTargetResolver
{
    /** @return Collection<int, Position> */
    public function options(User $actor, DispositionRecipient $parentRecipient): Collection
    {
        [, $assistantUnit] = $this->assistantScopeForActor($actor, $parentRecipient);

        return $this->eligiblePositionsQuery((int) $assistantUnit->getKey())
            ->whereDoesntHave('assignments', fn (Builder $assignments): Builder => $assignments
                ->where('user_id', $actor->getKey())
                ->where('started_at', '<=', now())
                ->whereNull('ended_at'))
            ->withCount([
                'assignments as active_assignments_count' => fn (Builder $assignments): Builder => $assignments
                    ->where('started_at', '<=', now())
                    ->whereNull('ended_at'),
            ])
            ->with([
                'positionLevel:id,code',
                'organizationalUnit:id,name',
                'activeAssignment.user:id,name,account_type,is_active,email_verified_at',
                'receivedDispositionRecipients' => fn ($recipients) => $recipients
                    ->whereHas('disposition', fn (Builder $disposition): Builder => $disposition
                        ->where('incoming_letter_id', $parentRecipient->disposition->incoming_letter_id))
                    ->with('disposition.parentRecipient.recipientPosition:id,name'),
            ])
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->each(function (Position $position): void {
                $assignedBy = $position->receivedDispositionRecipients
                    ->map(fn (DispositionRecipient $recipient): ?string => $recipient->disposition->parentRecipient?->recipientPosition?->name)
                    ->filter()
                    ->first();

                if (is_string($assignedBy)) {
                    $position->setAttribute('assigned_by_name', $assignedBy);
                }
            });
    }

    /**
     * @return array{0: Position, 1: OrganizationalUnit}
     */
    public function lockAssistantScope(PositionAssignment $assistantAssignment): array
    {
        $assistantPosition = $this->assistantPositionQuery()
            ->whereKey($assistantAssignment->position_id)
            ->lockForUpdate()
            ->first();

        if (! $assistantPosition instanceof Position
            || $assistantPosition->organizational_unit_id === null) {
            $this->throwOutOfScope();
        }

        $assistantUnit = OrganizationalUnit::query()
            ->whereKey($assistantPosition->organizational_unit_id)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (! $assistantUnit instanceof OrganizationalUnit) {
            $this->throwOutOfScope();
        }

        return [$assistantPosition, $assistantUnit];
    }

    /**
     * @param  list<int>  $positionIds
     * @return Collection<int, array{0: Position, 1: PositionAssignment, 2: OrganizationalUnit}>
     */
    public function lockAvailablePositions(
        array $positionIds,
        int $actorUserId,
        int $incomingLetterId,
        Position $assistantPosition,
        OrganizationalUnit $assistantUnit,
    ): Collection {
        $ids = array_values(array_unique($positionIds));

        sort($ids, SORT_NUMERIC);

        if (count($ids) < 1 || count($ids) > 50 || count($ids) !== count($positionIds)) {
            $this->throwUnavailable();
        }

        $positions = Position::query()
            ->whereIn('id', $ids)
            ->with('positionLevel:id,code,is_active')
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (Position $position): int => (int) $position->getKey());

        if ($positions->count() !== count($ids)) {
            $this->throwOutOfScope();
        }

        $targetUnits = $this->lockTargetUnits($positions);
        $resolved = new Collection;

        foreach ($ids as $positionId) {
            $position = $positions->get($positionId);

            if (! $position instanceof Position) {
                $this->throwOutOfScope();
            }

            $unit = $targetUnits->get($position->organizational_unit_id);

            if (! $unit instanceof OrganizationalUnit
                || ! $this->isEligibleDirectChild($position, $unit, $assistantPosition, $assistantUnit)) {
                $this->throwOutOfScope();
            }

            if ($this->isAlreadyAssignedToLetter($position, $incomingLetterId)) {
                $this->throwUnavailable();
            }

            $assignments = PositionAssignment::query()
                ->where('position_id', $position->getKey())
                ->where('started_at', '<=', now())
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->limit(2)
                ->get();

            if ($assignments->count() !== 1) {
                $this->throwUnavailable();
            }

            $assignment = $assignments->firstOrFail();
            $holder = User::query()
                ->whereKey($assignment->user_id)
                ->lockForUpdate()
                ->first();

            if (! $holder instanceof User
                || $holder->account_type !== AccountType::InternalAccount
                || ! $holder->is_active
                || ! $holder->hasVerifiedEmail()
                || (int) $assignment->user_id === $actorUserId) {
                $this->throwUnavailable();
            }

            $resolved->push([$position, $assignment, $unit]);
        }

        return $resolved;
    }

    /**
     * @return array{0: Position, 1: OrganizationalUnit}
     */
    private function assistantScopeForActor(User $actor, DispositionRecipient $parentRecipient): array
    {
        $assignments = PositionAssignment::query()
            ->where('user_id', $actor->getKey())
            ->where('position_id', $parentRecipient->recipient_position_id)
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->limit(2)
            ->get();

        if ($assignments->count() !== 1) {
            $this->throwOutOfScope();
        }

        $assignment = $assignments->firstOrFail();
        $assistantPosition = $this->assistantPositionQuery()
            ->whereKey($assignment->position_id)
            ->with('organizationalUnit:id,parent_id,name,is_active')
            ->first();

        if (! $assistantPosition instanceof Position
            || ! $assistantPosition->organizationalUnit instanceof OrganizationalUnit
            || ! $assistantPosition->organizationalUnit->is_active) {
            $this->throwOutOfScope();
        }

        return [$assistantPosition, $assistantPosition->organizationalUnit];
    }

    /** @return Builder<Position> */
    private function assistantPositionQuery(): Builder
    {
        return Position::query()
            ->where('is_active', true)
            ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                ->where('code', OrganizationCatalog::ASSISTANT_LEVEL)
                ->where('is_active', true));
    }

    /** @return Builder<Position> */
    private function eligiblePositionsQuery(int $assistantUnitId): Builder
    {
        return Position::query()
            ->where('is_active', true)
            ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                ->where('code', OrganizationCatalog::SECTION_HEAD_LEVEL)
                ->where('is_active', true))
            ->whereHas('organizationalUnit', fn (Builder $unit): Builder => $unit
                ->where('parent_id', $assistantUnitId)
                ->where('is_active', true));
    }

    /**
     * @param  Collection<int, Position>  $positions
     * @return Collection<int, OrganizationalUnit>
     */
    private function lockTargetUnits(Collection $positions): Collection
    {
        $unitIds = $positions
            ->pluck('organizational_unit_id')
            ->filter(static fn (mixed $id): bool => is_int($id) || ctype_digit((string) $id))
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->sort()
            ->values();

        if ($unitIds->count() !== $positions->count()) {
            $this->throwOutOfScope();
        }

        $units = OrganizationalUnit::query()
            ->whereIn('id', $unitIds->all())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (OrganizationalUnit $unit): int => (int) $unit->getKey());

        if ($units->count() !== $unitIds->count()) {
            $this->throwOutOfScope();
        }

        return $units;
    }

    private function isEligibleDirectChild(
        Position $position,
        OrganizationalUnit $unit,
        Position $assistantPosition,
        OrganizationalUnit $assistantUnit,
    ): bool {
        return $assistantPosition->is_active
            && $assistantUnit->is_active
            && $position->is_active
            && $position->positionLevel->code === OrganizationCatalog::SECTION_HEAD_LEVEL
            && $position->positionLevel->is_active
            && $unit->is_active
            && (int) $unit->parent_id === (int) $assistantUnit->getKey();
    }

    private function isAlreadyAssignedToLetter(Position $position, int $incomingLetterId): bool
    {
        return DispositionRecipient::query()
            ->where('recipient_position_id', $position->getKey())
            ->whereHas('disposition', fn (Builder $disposition): Builder => $disposition
                ->where('incoming_letter_id', $incomingLetterId))
            ->exists();
    }

    private function throwOutOfScope(): never
    {
        throw new NotFoundHttpException('Target disposisi tidak berada dalam jalur koordinasi Asisten ini.');
    }

    private function throwUnavailable(): never
    {
        throw ValidationException::withMessages([
            'recipient_position_ids' => 'Satu atau lebih Kepala Bagian sudah ditugaskan atau belum memiliki pejabat aktif yang dapat menerima disposisi.',
        ]);
    }
}
