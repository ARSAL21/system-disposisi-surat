<?php

namespace App\Models;

use App\Enums\LetterResponseReviewDecision;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $letter_response_dossier_id
 * @property int $document_version_id
 * @property LetterResponseReviewDecision $decision
 * @property string $reason
 * @property int $decided_by_user_id
 * @property int $decided_by_position_assignment_id
 * @property CarbonInterface $created_at
 */
class LetterResponseReview extends Model
{
    public const UPDATED_AT = null;

    /** @return array<string, class-string<LetterResponseReviewDecision>> */
    protected function casts(): array
    {
        return ['decision' => LetterResponseReviewDecision::class];
    }

    protected static function booted(): void
    {
        static::creating(fn (LetterResponseReview $review) => $review->public_id ??= (string) Str::ulid());
        static::updating(fn () => throw new LogicException('Response reviews are append-only.'));
        static::deleting(fn () => throw new LogicException('Response reviews cannot be deleted.'));
    }

    /** @return BelongsTo<LetterResponseDossier, $this> */
    public function dossier(): BelongsTo
    {
        return $this->belongsTo(LetterResponseDossier::class, 'letter_response_dossier_id');
    }

    /** @return BelongsTo<LetterResponseDocumentVersion, $this> */
    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(LetterResponseDocumentVersion::class, 'document_version_id');
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
