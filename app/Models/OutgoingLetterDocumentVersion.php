<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $outgoing_letter_id
 * @property int $version_number
 * @property int|null $replaces_version_id
 * @property string $storage_disk
 * @property string $storage_path
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $sha256
 * @property string $upload_note
 * @property CarbonInterface $created_at
 * @property-read OutgoingLetter $outgoingLetter
 * @property-read OutgoingLetterDocumentReview|null $review
 */
final class OutgoingLetterDocumentVersion extends Model
{
    public const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'size_bytes' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::creating(fn (self $version) => $version->public_id ??= (string) Str::ulid());
        self::updating(fn () => throw new LogicException('Outgoing document versions are immutable.'));
        self::deleting(fn () => throw new LogicException('Outgoing document versions cannot be deleted.'));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<OutgoingLetter, $this> */
    public function outgoingLetter(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetter::class);
    }

    /** @return BelongsTo<self, $this> */
    public function replacesVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function uploadedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'uploaded_by_position_assignment_id');
    }

    /** @return HasOne<OutgoingLetterDocumentReview, $this> */
    public function review(): HasOne
    {
        return $this->hasOne(OutgoingLetterDocumentReview::class);
    }
}
