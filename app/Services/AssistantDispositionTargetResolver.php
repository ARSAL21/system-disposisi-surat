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

class AssistantDispositionTargetResolver
{
    /** @return Collection<int, Position> */
    public function options(int $actorPositionId, int $actorUserId): Collection
    {
        return $this->eligiblePositionsQuery()
            ->where('positions.id', '!=', $actorPositionId)
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

    /** @return array{Position, PositionAssignment} */
    public function lockAvailablePosition(
        int $positionId,
        int $actorPositionId,
        int $actorUserId,
    ): array {
        if ($positionId === $actorPositionId) {
            $this->throwUnavailable();
        }

        $position = $this->eligiblePositionsQuery()
            ->whereKey($positionId)
            ->where('positions.id', '!=', $actorPositionId)
            ->lockForUpdate()
            ->first();

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

        return [$position, $assignment];
    }

    /** @return Builder<Position> */
    private function eligiblePositionsQuery(): Builder
    {
        return Position::query()
            ->where('is_active', true)
            ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                ->where('code', OrganizationCatalog::ASSISTANT_LEVEL)
                ->where('is_active', true));
    }

    private function throwUnavailable(): never
    {
        throw ValidationException::withMessages([
            'recipient_position_id' => 'Asisten tujuan tidak tersedia, tidak memiliki pejabat aktif, atau bukan bawahan yang sah.',
        ]);
    }
}
