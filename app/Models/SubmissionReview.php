<?php

namespace App\Models;

use App\Enums\SubmissionReviewOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $letter_submission_id
 * @property SubmissionReviewOutcome $outcome
 * @property array<string, bool> $checklist
 * @property string|null $note
 * @property int $created_by_user_id
 * @property int $created_by_position_assignment_id
 * @property Carbon $created_at
 * @property-read LetterSubmission $submission
 * @property-read User $createdBy
 * @property-read PositionAssignment $createdByPositionAssignment
 */
class SubmissionReview extends Model
{
    public const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'outcome' => SubmissionReviewOutcome::class,
            'checklist' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Submission reviews are append-only.'));
        static::deleting(fn () => throw new LogicException('Submission reviews are append-only.'));
    }

    /** @return BelongsTo<LetterSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(LetterSubmission::class, 'letter_submission_id');
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
}
