<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $letter_submission_id
 * @property string $storage_disk
 * @property string $storage_path
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $sha256
 * @property int $uploaded_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read LetterSubmission $submission
 * @property-read User $uploader
 * @property-read LetterDocument|null $letterDocument
 */
class SubmissionDocument extends Model
{
    /** @return BelongsTo<LetterSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(LetterSubmission::class, 'letter_submission_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /** @return HasOne<LetterDocument, $this> */
    public function letterDocument(): HasOne
    {
        return $this->hasOne(LetterDocument::class, 'source_submission_document_id');
    }
}
