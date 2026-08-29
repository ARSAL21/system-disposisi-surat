<?php

namespace App\Http\Resources;

use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrganizationalUnit */
class OrganizationalUnitResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $actor = $actor instanceof User ? $actor : null;

        return [
            'id' => $this->getKey(),
            'parent_id' => $this->parent_id,
            'code' => $this->code,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'parent' => $this->parent === null ? null : [
                'id' => $this->parent->getKey(),
                'name' => $this->parent->name,
            ],
            'children_count' => (int) ($this->children_count ?? 0),
            'positions_count' => (int) ($this->positions_count ?? 0),
            'capabilities' => [
                'update' => $actor?->can('update', $this->resource) ?? false,
                'change_status' => $actor?->can('changeStatus', $this->resource) ?? false,
            ],
            'links' => [
                'update' => route('back-office.organization.units.update', $this->resource),
                'status' => route('back-office.organization.units.status', $this->resource),
            ],
        ];
    }
}
