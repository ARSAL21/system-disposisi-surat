<?php

namespace App\Actions;

use App\Enums\AccountType;
use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\PositionLevel;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class GetPositionAssignmentWorkspace
{
    /**
     * @param  array{search: string, status: string, position_level_id: int|null, organizational_unit_id: int|null, selected_position: int|null}  $filters
     * @return array{
     *     positions: LengthAwarePaginator<int, Position>,
     *     selected_position: Position|null,
     *     history: LengthAwarePaginator<int, PositionAssignment>|null,
     *     levels: Collection<int, PositionLevel>,
     *     units: Collection<int, OrganizationalUnit>,
     *     users: Collection<int, User>,
     *     summary: array{positions: int, occupied: int, vacant: int, inactive: int}
     * }
     */
    public function execute(array $filters): array
    {
        $positions = Position::query()
            ->with(['positionLevel', 'organizationalUnit', 'activeAssignment.user'])
            ->withCount('assignments')
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('activeAssignment.user', fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")));
            })
            ->when($filters['status'] === 'occupied', fn ($query) => $query
                ->where('is_active', true)->whereHas('activeAssignment'))
            ->when($filters['status'] === 'vacant', fn ($query) => $query
                ->where('is_active', true)->whereDoesntHave('activeAssignment'))
            ->when($filters['status'] === 'inactive', fn ($query) => $query
                ->where('is_active', false))
            ->when($filters['position_level_id'], fn ($query, int $levelId) => $query
                ->where('position_level_id', $levelId))
            ->when($filters['organizational_unit_id'], fn ($query, int $unitId) => $query
                ->where('organizational_unit_id', $unitId))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $selectedPosition = $filters['selected_position'] === null
            ? null
            : Position::query()
                ->with(['positionLevel', 'organizationalUnit', 'activeAssignment.user'])
                ->withCount('assignments')
                ->findOrFail($filters['selected_position']);

        $history = $selectedPosition === null
            ? null
            : PositionAssignment::query()
                ->where('position_id', $selectedPosition->getKey())
                ->with(['user:id,name,email', 'assignedBy:id,name'])
                ->orderByDesc('started_at')
                ->paginate(10, pageName: 'history_page')
                ->withQueryString();

        $total = Position::query()->count();
        $occupied = Position::query()->where('is_active', true)->whereHas('activeAssignment')->count();

        return [
            'positions' => $positions,
            'selected_position' => $selectedPosition,
            'history' => $history,
            'levels' => PositionLevel::query()
                ->whereIn('code', OrganizationCatalog::positionLevelCodes())
                ->where('is_active', true)
                ->orderBy('hierarchy_order')
                ->get(),
            'units' => OrganizationalUnit::query()->where('is_active', true)->orderBy('name')->get(),
            'users' => User::query()
                ->where('account_type', AccountType::InternalAccount->value)
                ->where('is_active', true)
                ->whereNotNull('email_verified_at')
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'email']),
            'summary' => [
                'positions' => $total,
                'occupied' => $occupied,
                'vacant' => Position::query()->where('is_active', true)->whereDoesntHave('activeAssignment')->count(),
                'inactive' => Position::query()->where('is_active', false)->count(),
            ],
        ];
    }
}
