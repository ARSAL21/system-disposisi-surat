<?php

namespace App\Http\Resources;

use App\Models\PositionAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PositionAssignment */
class PositionAssignmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'started_at' => $this->started_at->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'is_active' => $this->isActive(),
            'user' => [
                'id' => $this->user->getKey(),
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'assigned_by' => $this->assignedBy === null ? null : [
                'id' => $this->assignedBy->getKey(),
                'name' => $this->assignedBy->name,
            ],
            'links' => [
                'end' => route('back-office.organization.assignments.end', $this->resource),
            ],
        ];
    }
}
