<?php

namespace App\Http\Resources;

use App\Models\PositionLevel;
use App\Organization\OrganizationCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PositionLevel */
class PositionLevelResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'code' => $this->code,
            'name' => $this->name,
            'hierarchy_order' => $this->hierarchy_order,
            'is_active' => $this->is_active,
            'is_protected' => OrganizationCatalog::isProtectedPositionLevel($this->code),
            'position_count' => (int) ($this->positions_count ?? 0),
        ];
    }
}
