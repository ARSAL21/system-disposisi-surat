<?php

namespace App\Http\Resources;

use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Position */
class OrganizationPositionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $actor = $actor instanceof User ? $actor : null;

        return [
            'id' => $this->getKey(),
            'position_level_id' => $this->position_level_id,
            'organizational_unit_id' => $this->organizational_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'level' => [
                'id' => $this->positionLevel->getKey(),
                'code' => $this->positionLevel->code,
                'name' => $this->positionLevel->name,
                'hierarchy_order' => $this->positionLevel->hierarchy_order,
            ],
            'unit' => $this->organizationalUnit === null ? null : [
                'id' => $this->organizationalUnit->getKey(),
                'name' => $this->organizationalUnit->name,
            ],
            'active_assignment' => $this->activeAssignment === null ? null : [
                'id' => $this->activeAssignment->getKey(),
                'started_at' => $this->activeAssignment->started_at->toISOString(),
                'user' => [
                    'id' => $this->activeAssignment->user->getKey(),
                    'name' => $this->activeAssignment->user->name,
                    'email' => $this->activeAssignment->user->email,
                ],
                'links' => [
                    'end' => route('back-office.organization.assignments.end', $this->activeAssignment),
                ],
            ],
            'assignment_count' => (int) ($this->assignments_count ?? 0),
            'capabilities' => [
                'update' => $actor?->can('update', $this->resource) ?? false,
                'change_status' => $actor?->can('changeStatus', $this->resource) ?? false,
            ],
            'links' => [
                'update' => route('back-office.organization.positions.update', $this->resource),
                'status' => route('back-office.organization.positions.status', $this->resource),
                'assign' => route('back-office.organization.assignments.store', $this->resource),
                'replace' => route('back-office.organization.assignments.replace', $this->resource),
            ],
        ];
    }
}
