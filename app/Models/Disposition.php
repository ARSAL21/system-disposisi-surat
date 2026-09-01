<?php

namespace App\Models;

use App\Policies\DispositionPolicy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * @property int $id
 * @property int $incoming_letter_id
 * @property int|null $source_route_id
 * @property int|null $parent_recipient_id
 * @property int $created_by_user_id
 * @property int $created_by_position_assignment_id
 * @property string|null $instruction_note
 * @property CarbonInterface $created_at
 * @property-read IncomingLetter $incomingLetter
 * @property-read LetterRoute|null $sourceRoute
 * @property-read DispositionRecipient|null $parentRecipient
 * @property-read User $createdBy
 * @property-read PositionAssignment $createdByPositionAssignment
 * @property-read Collection<int, DispositionRecipient> $recipients
 * @property-read Collection<int, InstructionLabel> $instructionLabels
 */
#[UsePolicy(DispositionPolicy::class)]
class Disposition extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::creating(function (Disposition $disposition): void {
            $hasSourceRoute = $disposition->source_route_id !== null;
            $hasParentRecipient = $disposition->parent_recipient_id !== null;

            if ($hasSourceRoute === $hasParentRecipient) {
                throw new LogicException('A disposition must have exactly one workflow source.');
            }
        });

        static::updating(
            fn () => throw new LogicException('Dispositions are historical records and cannot be updated.'),
        );

        static::deleting(
            fn () => throw new LogicException('Dispositions are historical records and cannot be deleted.'),
        );
    }

    /** @return BelongsTo<IncomingLetter, $this> */
    public function incomingLetter(): BelongsTo
    {
        return $this->belongsTo(IncomingLetter::class);
    }

    /** @return BelongsTo<LetterRoute, $this> */
    public function sourceRoute(): BelongsTo
    {
        return $this->belongsTo(LetterRoute::class, 'source_route_id');
    }

    /** @return BelongsTo<DispositionRecipient, $this> */
    public function parentRecipient(): BelongsTo
    {
        return $this->belongsTo(DispositionRecipient::class, 'parent_recipient_id');
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

    /** @return HasMany<DispositionRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(DispositionRecipient::class);
    }

    /** @return BelongsToMany<InstructionLabel, $this, DispositionInstructionLabel> */
    public function instructionLabels(): BelongsToMany
    {
        return $this->belongsToMany(InstructionLabel::class, 'disposition_instruction_label')
            ->using(DispositionInstructionLabel::class)
            ->orderBy('instruction_labels.sort_order')
            ->orderBy('instruction_labels.id');
    }
}
