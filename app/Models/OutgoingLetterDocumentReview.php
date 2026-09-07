<?php

namespace App\Models;

use App\Enums\OutgoingLetterReviewDecision;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $outgoing_letter_document_version_id
 * @property OutgoingLetterReviewDecision $decision
 * @property string|null $note
 * @property int $decided_by_user_id
 * @property int $decided_by_position_assignment_id
 * @property CarbonInterface $created_at
 * @property-read User $decidedBy
 * @property-read PositionAssignment $decidedByPositionAssignment
 */
final class OutgoingLetterDocumentReview extends Model
{
    public const UPDATED_AT = null;

    /** @return array<string, class-string<OutgoingLetterReviewDecision>|string> */
    protected function casts(): array
    {
        return ['decision' => OutgoingLetterReviewDecision::class];
    }

    protected static function booted(): void
    {
        self::creating(fn (self $review) => $review->public_id ??= (string) Str::ulid());
        self::updating(fn () => throw new LogicException('Outgoing document reviews are append-only.'));
        self::deleting(fn () => throw new LogicException('Outgoing document reviews cannot be deleted.'));
    }

    /** @return BelongsTo<OutgoingLetterDocumentVersion, $this> */
    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetterDocumentVersion::class, 'outgoing_letter_document_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function decidedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'decided_by_position_assignment_id');
    }
}
