<?php

namespace App\Actions;

use App\Models\OrganizationalUnit;
use App\Models\Position;
use App\Models\PositionLevel;
use App\Organization\OrganizationCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class GetOrganizationStructureWorkspace
{
    /**
     * @param  array{section: string, search: string, status: string, position_level_id: int|null, organizational_unit_id: int|null}  $filters
     * @return array{
     *     levels: Collection<int, PositionLevel>,
     *     units: LengthAwarePaginator<int, OrganizationalUnit>|null,
     *     positions: LengthAwarePaginator<int, Position>|null,
     *     unit_options: Collection<int, OrganizationalUnit>,
     *     summary: array{levels: int, active_units: int, active_positions: int, occupied_positions: int}
     * }
     */
    public function execute(array $filters): array
    {
        $levels = PositionLevel::query()
            ->whereIn('code', OrganizationCatalog::positionLevelCodes())
            ->withCount('positions')
            ->orderBy('hierarchy_order')
            ->get();

        $units = $filters['section'] === 'units'
            ? $this->units($filters)
            : null;
        $positions = $filters['section'] === 'positions'
            ? $this->positions($filters)
            : null;

        return [
            'levels' => $levels,
            'units' => $units,
            'positions' => $positions,
            'unit_options' => OrganizationalUnit::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'parent_id', 'code', 'name', 'is_active']),
            'summary' => [
                'levels' => $levels->count(),
                'active_units' => OrganizationalUnit::query()->where('is_active', true)->count(),
                'active_positions' => Position::query()->where('is_active', true)->count(),
                'occupied_positions' => Position::query()
                    ->where('is_active', true)
                    ->whereHas('activeAssignment')
                    ->count(),
            ],
        ];
    }

    /**
     * @param  array{section: string, search: string, status: string, position_level_id: int|null, organizational_unit_id: int|null}  $filters
     * @return LengthAwarePaginator<int, OrganizationalUnit>
     */
    private function units(array $filters): LengthAwarePaginator
    {
        return OrganizationalUnit::query()
            ->with('parent:id,name')
            ->withCount(['children', 'positions'])
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            })
            ->when($filters['status'] !== 'all', fn ($query) => $query
                ->where('is_active', $filters['status'] === 'active'))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();
    }

    /**
     * @param  array{section: string, search: string, status: string, position_level_id: int|null, organizational_unit_id: int|null}  $filters
     * @return LengthAwarePaginator<int, Position>
     */
    private function positions(array $filters): LengthAwarePaginator
    {
        return Position::query()
            ->with(['positionLevel', 'organizationalUnit', 'activeAssignment.user'])
            ->withCount('assignments')
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            })
            ->when($filters['status'] !== 'all', fn ($query) => $query
                ->where('is_active', $filters['status'] === 'active'))
            ->when($filters['position_level_id'], fn ($query, int $levelId) => $query
                ->where('position_level_id', $levelId))
            ->when($filters['organizational_unit_id'], fn ($query, int $unitId) => $query
                ->where('organizational_unit_id', $unitId))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();
    }
}
