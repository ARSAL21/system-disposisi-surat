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
     *     tree: array{root_units: list<array<string, mixed>>, unassigned_positions: list<array<string, mixed>>},
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
            'tree' => $this->tree(),
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
     * @return array{
     *     root_units: list<array<string, mixed>>,
     *     unassigned_positions: list<array<string, mixed>>
     * }
     */
    public function tree(): array
    {
        $allUnits = OrganizationalUnit::query()
            ->with([
                'positions' => fn ($q) => $q->with(['positionLevel', 'activeAssignment.user'])->orderBy('position_level_id'),
            ])
            ->withCount(['children', 'positions'])
            ->orderBy('name')
            ->get();

        /** @var list<array<string, mixed>> $unassignedPositions */
        $unassignedPositions = array_values(Position::query()
            ->whereNull('organizational_unit_id')
            ->with(['positionLevel', 'activeAssignment.user'])
            ->orderBy('position_level_id')
            ->get()
            ->map(fn (Position $pos): array => $this->transformPosition($pos))
            ->all());

        $nestedUnits = $this->buildUnitTree($allUnits, null);

        return [
            'root_units' => $nestedUnits,
            'unassigned_positions' => $unassignedPositions,
        ];
    }

    /**
     * @param  Collection<int, OrganizationalUnit>  $units
     * @return list<array<string, mixed>>
     */
    private function buildUnitTree(Collection $units, ?int $parentId): array
    {
        /** @var list<array<string, mixed>> $tree */
        $tree = array_values($units
            ->where('parent_id', $parentId)
            ->map(function (OrganizationalUnit $unit) use ($units): array {
                return [
                    'id' => $unit->id,
                    'parent_id' => $unit->parent_id,
                    'code' => $unit->code,
                    'name' => $unit->name,
                    'is_active' => $unit->is_active,
                    'children_count' => $unit->children_count,
                    'positions_count' => $unit->positions_count,
                    'children' => $this->buildUnitTree($units, $unit->id),
                    'positions' => array_values($unit->positions
                        ->map(fn (Position $pos): array => $this->transformPosition($pos))
                        ->all()),
                ];
            })
            ->all());

        return $tree;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformPosition(Position $pos): array
    {
        return [
            'id' => $pos->id,
            'code' => $pos->code,
            'name' => $pos->name,
            'is_active' => $pos->is_active,
            'organizational_unit_id' => $pos->organizational_unit_id,
            'level' => [
                'id' => $pos->positionLevel->id,
                'code' => $pos->positionLevel->code,
                'name' => $pos->positionLevel->name,
                'hierarchy_order' => $pos->positionLevel->hierarchy_order,
            ],
            'active_assignment' => $pos->activeAssignment === null ? null : [
                'id' => $pos->activeAssignment->id,
                'started_at' => (string) $pos->activeAssignment->started_at,
                'user' => [
                    'id' => $pos->activeAssignment->user->id,
                    'name' => $pos->activeAssignment->user->name,
                    'email' => $pos->activeAssignment->user->email,
                ],
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
