<?php

namespace App\Models;

use App\Policies\PositionAssignmentPolicy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int $user_id
 * @property int $position_id
 * @property CarbonInterface $started_at
 * @property CarbonInterface|null $ended_at
 * @property int|null $assigned_by_user_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read User $user
 * @property-read Position $position
 * @property-read User|null $assignedBy
 */
#[UsePolicy(PositionAssignmentPolicy::class)]
class PositionAssignment extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s.u';
    }

    protected static function booted(): void
    {
        static::updating(function (PositionAssignment $assignment): void {
            if (! $assignment->isDirty('ended_at') || count($assignment->getDirty()) !== 1) {
                throw new LogicException('Historical position assignment fields are immutable.');
            }

            if ($assignment->getRawOriginal('ended_at') !== null) {
                throw new LogicException('A position assignment can only be ended once.');
            }

            if ($assignment->ended_at === null || ! $assignment->ended_at->greaterThan($assignment->started_at)) {
                throw new LogicException('Position assignment end time must be after its start time.');
            }
        });

        static::deleting(
            fn () => throw new LogicException('Position assignments are historical records and cannot be deleted.'),
        );
    }

    /**
     * @param  Builder<PositionAssignment>  $query
     * @return Builder<PositionAssignment>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Position, $this> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
