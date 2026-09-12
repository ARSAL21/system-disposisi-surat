<?php

namespace App\Models;

use App\Enums\ManualSignatureReviewDecision;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $outgoing_letter_document_version_id
 * @property ManualSignatureReviewDecision $decision
 * @property string|null $note
 * @property int $reviewed_by_user_id
 * @property int $reviewed_by_position_assignment_id
 * @property CarbonInterface $created_at
 */
final class OutgoingLetterManualSignatureReview extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['decision' => ManualSignatureReviewDecision::class];
    }

    protected static function booted(): void
    {
        self::creating(fn (self $review) => $review->public_id ??= (string) Str::ulid());
        self::updating(fn () => throw new LogicException('Pemeriksaan scan tanda tangan bersifat append-only.'));
        self::deleting(fn () => throw new LogicException('Pemeriksaan scan tanda tangan tidak dapat dihapus.'));
    }

    /** @return BelongsTo<OutgoingLetterDocumentVersion, $this> */
    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetterDocumentVersion::class, 'outgoing_letter_document_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function reviewedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'reviewed_by_position_assignment_id');
    }
}
