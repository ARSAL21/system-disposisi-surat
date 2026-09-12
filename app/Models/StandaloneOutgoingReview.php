<?php

namespace App\Models;

use App\Enums\StandaloneOutgoingReviewDecision;
use App\Enums\StandaloneOutgoingReviewStage;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $standalone_outgoing_draft_id
 * @property int $standalone_outgoing_document_version_id
 * @property StandaloneOutgoingReviewStage $stage
 * @property StandaloneOutgoingReviewDecision $decision
 * @property string|null $reason
 * @property int $decided_by_user_id
 * @property int $decided_by_position_assignment_id
 * @property CarbonInterface $created_at
 */
class StandaloneOutgoingReview extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'stage' => StandaloneOutgoingReviewStage::class,
            'decision' => StandaloneOutgoingReviewDecision::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $review) => $review->public_id ??= (string) Str::ulid());
        static::updating(fn () => throw new LogicException('Keputusan draf surat keluar tidak dapat diubah.'));
        static::deleting(fn () => throw new LogicException('Keputusan draf surat keluar tidak dapat dihapus.'));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<StandaloneOutgoingDraft, $this> */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(StandaloneOutgoingDraft::class, 'standalone_outgoing_draft_id');
    }

    /** @return BelongsTo<StandaloneOutgoingDocumentVersion, $this> */
    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(StandaloneOutgoingDocumentVersion::class, 'standalone_outgoing_document_version_id');
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
