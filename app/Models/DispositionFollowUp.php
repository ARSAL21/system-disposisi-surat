<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int $disposition_recipient_id
 * @property int $created_by_user_id
 * @property int $created_by_position_assignment_id
 * @property string $note
 * @property CarbonInterface $created_at
 * @property-read DispositionRecipient $dispositionRecipient
 * @property-read User $createdBy
 * @property-read PositionAssignment $createdByPositionAssignment
 */
class DispositionFollowUp extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::creating(function (DispositionFollowUp $followUp): void {
            $note = $followUp->getAttribute('note');
            $trimmedNote = is_string($note) ? trim($note) : '';
            $noteLength = mb_strlen($trimmedNote);

            if (! is_int($followUp->getAttribute('disposition_recipient_id'))
                || ! is_int($followUp->getAttribute('created_by_user_id'))
                || ! is_int($followUp->getAttribute('created_by_position_assignment_id'))
                || ! is_string($note)
                || $trimmedNote !== $note
                || $noteLength < 10
                || $noteLength > 2000
            ) {
                throw new LogicException('Disposition follow-ups require complete server-owned context and a valid note.');
            }
        });

        static::updating(
            fn () => throw new LogicException('Disposition follow-ups are historical records and cannot be updated.'),
        );

        static::deleting(
            fn () => throw new LogicException('Disposition follow-ups are historical records and cannot be deleted.'),
        );
    }

    /** @return BelongsTo<DispositionRecipient, $this> */
    public function dispositionRecipient(): BelongsTo
    {
        return $this->belongsTo(DispositionRecipient::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function createdByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'created_by_position_assignment_id');
    }
}
