<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SectionHeadDispositionTargetResolver
{
    /** @return Collection<int, Position> */
    public function options(int $actorUserId): Collection
    {
        return $this->eligiblePositionsQuery()
            ->whereDoesntHave('assignments', fn (Builder $assignments): Builder => $assignments
                ->where('user_id', $actorUserId)
                ->where('started_at', '<=', now())
                ->whereNull('ended_at'))
            ->whereHas(
                'assignments',
                fn (Builder $assignments): Builder => $assignments
                    ->where('started_at', '<=', now())
                    ->whereNull('ended_at'),
                '=',
                1,
            )
            ->whereHas('assignments', fn (Builder $assignments): Builder => $assignments
                ->where('started_at', '<=', now())
                ->whereNull('ended_at')
                ->whereHas('user', fn (Builder $user): Builder => $user
                    ->where('account_type', AccountType::InternalAccount->value)
                    ->where('is_active', true)
                    ->whereNotNull('email_verified_at')))
            ->withCount([
                'assignments as active_assignments_count' => fn (Builder $assignments): Builder => $assignments
                    ->where('started_at', '<=', now())
                    ->whereNull('ended_at'),
            ])
            ->with([
                'positionLevel:id,code',
                'organizationalUnit:id,name',
                'activeAssignment.user:id,name,account_type,is_active,email_verified_at',
            ])
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<int>  $positionIds
     * @return Collection<int, array{Position, PositionAssignment}>
     */
    public function lockAvailablePositions(array $positionIds, int $actorUserId): Collection
    {
        $ids = array_values(array_unique($positionIds));

        sort($ids, SORT_NUMERIC);

        if (count($ids) < 1 || count($ids) > 50 || count($ids) !== count($positionIds)) {
            $this->throwUnavailable();
        }

        $positions = $this->eligiblePositionsQuery()
            ->whereIn('positions.id', $ids)
            ->orderBy('positions.id')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (Position $position): int => (int) $position->getKey());

        if ($positions->count() !== count($ids)) {
            $this->throwUnavailable();
        }

        $resolved = new Collection;

        foreach ($ids as $positionId) {
            $position = $positions->get($positionId);

            if (! $position instanceof Position) {
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
                || (int) $assignment->user_id === $actorUserId
            ) {
                $this->throwUnavailable();
            }

            $resolved->push([$position, $assignment]);
        }

        return $resolved;
    }

    /** @return Builder<Position> */
    private function eligiblePositionsQuery(): Builder
    {
        return Position::query()
            ->where('is_active', true)
            ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                ->where('code', OrganizationCatalog::SECTION_HEAD_LEVEL)
                ->where('is_active', true));
    }

    private function throwUnavailable(): never
    {
        throw ValidationException::withMessages([
            'recipient_position_ids' => 'Satu atau lebih Kepala Bagian tujuan tidak tersedia, tidak memiliki pejabat aktif, atau bukan bawahan yang sah.',
        ]);
    }
}
