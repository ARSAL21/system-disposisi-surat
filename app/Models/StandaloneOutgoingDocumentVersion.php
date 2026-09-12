<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $standalone_outgoing_draft_id
 * @property int $version_number
 * @property int|null $replaces_version_id
 * @property string $storage_disk
 * @property string $storage_path
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $sha256
 * @property string|null $revision_note
 * @property int $uploaded_by_user_id
 * @property int $uploaded_by_position_assignment_id
 * @property CarbonInterface $created_at
 */
class StandaloneOutgoingDocumentVersion extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['version_number' => 'integer', 'size_bytes' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $version) => $version->public_id ??= (string) Str::ulid());
        static::updating(fn () => throw new LogicException('Versi draf surat keluar tidak dapat diubah.'));
        static::deleting(fn () => throw new LogicException('Versi draf surat keluar tidak dapat dihapus.'));
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
}
