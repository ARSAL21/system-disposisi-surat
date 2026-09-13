<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ExpertConsultationReport extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Expert consultation reports are immutable.'));
        static::deleting(fn () => throw new LogicException('Expert consultation reports cannot be deleted.'));
    }

    /** @return BelongsTo<ExpertConsultation, $this> */
    public function expertConsultation(): BelongsTo
    {
        return $this->belongsTo(ExpertConsultation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function reportedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'reported_by_position_assignment_id');
    }
}
