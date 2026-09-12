<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\InitialLetterRoutePath;
use App\Models\Position;
use App\Models\PositionAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ExecutiveRoutingTargetResolver
{
    /**
     * @return list<array{path: string, label: string, description: string, position: Position|null}>
     */
    public function options(): array
    {
        return array_values(collect(InitialLetterRoutePath::cases())
            ->map(function (InitialLetterRoutePath $path): array {
                $position = $this->eligiblePositionQuery($path)
                    ->with('activeAssignment.user:id,name,account_type,is_active,email_verified_at')
                    ->first();

                return [
                    'path' => $path->value,
                    'label' => $path->label(),
                    'description' => $path->description(),
                    'position' => $position,
                ];
            })
            ->values()
            ->all());
    }

    /**
     * Lock and revalidate the selected executive Position and its current
     * holder within the routing transaction.
     *
     * @return array{Position, PositionAssignment}
     */
    public function lockAvailablePosition(InitialLetterRoutePath $path): array
    {
        $position = $this->eligiblePositionQuery($path)
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
    private function eligiblePositionQuery(InitialLetterRoutePath $path): Builder
    {
        return Position::query()
            ->where('is_active', true)
            ->where('code', $path->targetPositionCode())
            ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                ->where('code', $path->targetLevelCode())
                ->where('is_active', true));
    }

    private function throwUnavailable(): never
    {
        throw ValidationException::withMessages([
            'route_path' => 'Jalur routing tidak tersedia atau jabatan tujuan belum memiliki pejabat aktif.',
        ]);
    }
}
