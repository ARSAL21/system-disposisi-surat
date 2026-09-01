<?php

namespace App\Models;

use App\Enums\LetterRouteStatus;
use App\Policies\LetterRoutePolicy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * @property int $id
 * @property int $incoming_letter_id
 * @property int $recipient_position_id
 * @property int $routed_by_user_id
 * @property int|null $routed_by_position_assignment_id
 * @property LetterRouteStatus $status
 * @property CarbonInterface $routed_at
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read IncomingLetter $incomingLetter
 * @property-read Position $recipientPosition
 * @property-read User $routedBy
 * @property-read PositionAssignment|null $routedByPositionAssignment
 * @property-read Disposition|null $disposition
 */
#[UsePolicy(LetterRoutePolicy::class)]
class LetterRoute extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => LetterRouteStatus::class,
            'routed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (LetterRoute $letterRoute): void {
            $allowedFields = ['status', 'completed_at', 'updated_at'];
            $unexpectedFields = array_diff(array_keys($letterRoute->getDirty()), $allowedFields);

            if ($unexpectedFields !== []) {
                throw new LogicException('Historical letter routing fields are immutable.');
            }

            $originalStatus = $letterRoute->getRawOriginal('status');
            if (! $letterRoute->isDirty('status')
                || ! $letterRoute->isDirty('completed_at')
                || $originalStatus !== LetterRouteStatus::Pending->value
                || $letterRoute->status !== LetterRouteStatus::Completed
                || $letterRoute->completed_at === null
            ) {
                throw new LogicException('Letter routes can only transition from PENDING to COMPLETED.');
            }
        });

        static::deleting(
            fn () => throw new LogicException('Letter routes are historical records and cannot be deleted.'),
        );
    }

    /** @return BelongsTo<IncomingLetter, $this> */
    public function incomingLetter(): BelongsTo
    {
        return $this->belongsTo(IncomingLetter::class);
    }

    /** @return BelongsTo<Position, $this> */
    public function recipientPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'recipient_position_id');
    }

    /** @return BelongsTo<User, $this> */
    public function routedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'routed_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function routedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'routed_by_position_assignment_id');
    }

    /** @return HasOne<Disposition, $this> */
    public function disposition(): HasOne
    {
        return $this->hasOne(Disposition::class, 'source_route_id');
    }
}
