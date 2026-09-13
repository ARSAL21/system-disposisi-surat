<?php

namespace App\Models;

use App\Enums\PositionRelationshipType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A formal relationship that complements, but never replaces, the structural
 * organizational-unit tree.
 */
class PositionRelationship extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'relationship_type' => PositionRelationshipType::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Position, $this> */
    public function sourcePosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'source_position_id');
    }

    /** @return BelongsTo<Position, $this> */
    public function targetPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'target_position_id');
    }
}
