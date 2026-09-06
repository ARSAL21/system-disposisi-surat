<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $letter_response_document_id
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
 * @property-read LetterResponseDocument $document
 * @property-read User $uploadedBy
 * @property-read Collection<int, self> $sourceVersions
 */
class LetterResponseDocumentVersion extends Model
{
    public const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (LetterResponseDocumentVersion $version) => $version->public_id ??= (string) Str::ulid());
        static::updating(fn () => throw new LogicException('Response document versions are immutable.'));
        static::deleting(fn () => throw new LogicException('Response document versions cannot be deleted.'));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<LetterResponseDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(LetterResponseDocument::class, 'letter_response_document_id');
    }

    /** @return BelongsTo<LetterResponseDocumentVersion, $this> */
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

    /** @return BelongsToMany<LetterResponseDocumentVersion, $this> */
    public function sourceVersions(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'letter_response_document_sources',
            'target_version_id',
            'source_version_id',
        )->withPivot('created_at');
    }
}
