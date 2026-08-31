<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $incoming_letter_id
 * @property int|null $source_submission_document_id
 * @property int $version_number
 * @property int|null $replaces_document_id
 * @property string $storage_disk
 * @property string $storage_path
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $sha256
 * @property string|null $correction_reason
 * @property int $uploaded_by_user_id
 * @property Carbon $created_at
 * @property-read IncomingLetter $incomingLetter
 * @property-read SubmissionDocument|null $sourceSubmissionDocument
 * @property-read LetterDocument|null $replacesDocument
 * @property-read Collection<int, LetterDocument> $replacementDocuments
 * @property-read User $uploadedBy
 */
class LetterDocument extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Letter document versions are immutable.'));
        static::deleting(fn () => throw new LogicException('Letter document versions cannot be deleted.'));
    }

    /** @return BelongsTo<IncomingLetter, $this> */
    public function incomingLetter(): BelongsTo
    {
        return $this->belongsTo(IncomingLetter::class);
    }

    /** @return BelongsTo<SubmissionDocument, $this> */
    public function sourceSubmissionDocument(): BelongsTo
    {
        return $this->belongsTo(SubmissionDocument::class, 'source_submission_document_id');
    }

    /** @return BelongsTo<LetterDocument, $this> */
    public function replacesDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_document_id');
    }

    /** @return HasMany<LetterDocument, $this> */
    public function replacementDocuments(): HasMany
    {
        return $this->hasMany(self::class, 'replaces_document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
