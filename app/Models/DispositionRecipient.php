<?php

namespace App\Models;

use App\Enums\DispositionRecipientStatus;
use App\Policies\DispositionRecipientPolicy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * @property int $id
 * @property int $disposition_id
 * @property int $recipient_position_id
 * @property DispositionRecipientStatus $status
 * @property CarbonInterface|null $received_at
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $completed_at
 * @property int|null $completed_by_user_id
 * @property int|null $completed_by_position_assignment_id
 * @property string|null $completion_note
 * @property-read Disposition $disposition
 * @property-read Position $recipientPosition
 * @property-read User|null $completedBy
 * @property-read PositionAssignment|null $completedByPositionAssignment
 */
#[UsePolicy(DispositionRecipientPolicy::class)]
class DispositionRecipient extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => DispositionRecipientStatus::class,
            'received_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DispositionRecipient $recipient): void {
            if ($recipient->status !== DispositionRecipientStatus::Pending
                || $recipient->received_at === null
                || $recipient->started_at !== null
                || $recipient->completed_at !== null
                || $recipient->completed_by_user_id !== null
                || $recipient->completed_by_position_assignment_id !== null
                || $recipient->completion_note !== null
            ) {
                throw new LogicException('New disposition recipients must start as a clean PENDING branch.');
            }
        });

        static::updating(function (DispositionRecipient $recipient): void {
            $allowedFields = [
                'status',
                'started_at',
                'completed_at',
                'completed_by_user_id',
                'completed_by_position_assignment_id',
                'completion_note',
                'updated_at',
            ];
            $unexpectedFields = array_diff(array_keys($recipient->getDirty()), $allowedFields);

            if ($unexpectedFields !== []) {
                throw new LogicException('Historical disposition recipient fields are immutable.');
            }

            $originalStatus = DispositionRecipientStatus::tryFrom(
                (string) $recipient->getRawOriginal('status'),
            );

            $isStarting = $originalStatus === DispositionRecipientStatus::Pending
                && $recipient->status === DispositionRecipientStatus::InProgress
                && $recipient->started_at !== null
                && $recipient->completed_at === null
                && $recipient->completed_by_user_id === null
                && $recipient->completed_by_position_assignment_id === null
                && $recipient->completion_note === null;
            $startedAtWasPreserved = $originalStatus === DispositionRecipientStatus::Pending
                ? $recipient->started_at === null
                : ! $recipient->isDirty('started_at');
            $isCompleting = in_array($originalStatus, [
                DispositionRecipientStatus::Pending,
                DispositionRecipientStatus::InProgress,
            ], true)
                && $recipient->status === DispositionRecipientStatus::Completed
                && $startedAtWasPreserved
                && $recipient->completed_at !== null
                && $recipient->completed_by_user_id !== null
                && $recipient->completed_by_position_assignment_id !== null;

            if (! $recipient->isDirty('status') || (! $isStarting && ! $isCompleting)) {
                throw new LogicException('Invalid disposition recipient status transition.');
            }
        });

        static::deleting(
            fn () => throw new LogicException('Disposition recipients are historical records and cannot be deleted.'),
        );
    }

    /** @return BelongsTo<Disposition, $this> */
    public function disposition(): BelongsTo
    {
        return $this->belongsTo(Disposition::class);
    }

    /** @return BelongsTo<Position, $this> */
    public function recipientPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'recipient_position_id');
    }

    /** @return BelongsTo<User, $this> */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function completedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'completed_by_position_assignment_id');
    }

    /** @return HasMany<Disposition, $this> */
    public function childDispositions(): HasMany
    {
        return $this->hasMany(Disposition::class, 'parent_recipient_id');
    }
}
