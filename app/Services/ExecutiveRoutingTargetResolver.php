<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ExecutiveRoutingTargetResolver
{
    /** @return Collection<int, Position> */
    public function options(): Collection
    {
        return $this->eligiblePositionsQuery()
            ->with('activeAssignment.user:id,name,account_type,is_active,email_verified_at')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * Lock and revalidate the selected executive Position and its current
     * holder within the routing transaction.
     *
     * @return array{Position, PositionAssignment}
     */
    public function lockAvailablePosition(int $positionId): array
    {
        $position = $this->eligiblePositionsQuery()
            ->whereKey($positionId)
            ->lockForUpdate()
            ->first();

        if (! $position instanceof Position) {
            $this->throwUnavailable();
        }

        $assignments = PositionAssignment::query()
            ->where('position_id', $position->getKey())
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->whereHas('user', fn (Builder $user): Builder => $user
                ->where('account_type', AccountType::InternalAccount->value)
                ->where('is_active', true)
                ->whereNotNull('email_verified_at'))
            ->lockForUpdate()
            ->limit(2)
            ->get();

        if ($assignments->count() !== 1) {
            $this->throwUnavailable();
        }

        return [$position, $assignments->firstOrFail()];
    }

    /** @return Builder<Position> */
    private function eligiblePositionsQuery(): Builder
    {
        return Position::query()
            ->where('is_active', true)
            ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                ->where('code', OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL)
                ->where('is_active', true));
    }

    private function throwUnavailable(): never
    {
        throw ValidationException::withMessages([
            'target_position_id' => 'Pimpinan tujuan tidak tersedia atau tidak memiliki pejabat aktif.',
        ]);
    }
}
