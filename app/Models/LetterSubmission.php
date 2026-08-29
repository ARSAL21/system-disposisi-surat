<?php

namespace App\Models;

use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $public_id
 * @property SubmissionSource $source
 * @property SubmissionStatus $status
 * @property int|null $submitted_by_user_id
 * @property int|null $recorded_by_user_id
 * @property string $sender_organization_name
 * @property string $contact_name
 * @property string $contact_email
 * @property string|null $contact_phone
 * @property string|null $external_letter_number
 * @property CarbonInterface|null $external_letter_date
 * @property string $subject
 * @property string|null $summary
 * @property CarbonInterface|null $submitted_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read User|null $submitter
 * @property-read User|null $recorder
 * @property-read SubmissionDocument|null $document
 * @property-read Collection<int, SubmissionReview> $reviews
 * @property-read SubmissionReview|null $latestReview
 * @property-read Collection<int, SubmissionDecision> $decisions
 * @property-read SubmissionDecision|null $latestDecision
 * @property-read IncomingLetter|null $incomingLetter
 */
class LetterSubmission extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => SubmissionSource::class,
            'status' => SubmissionStatus::class,
            'external_letter_date' => 'date',
            'submitted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /** @return HasOne<SubmissionDocument, $this> */
    public function document(): HasOne
    {
        return $this->hasOne(SubmissionDocument::class);
    }

    /** @return HasMany<SubmissionReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(SubmissionReview::class);
    }

    /** @return HasOne<SubmissionReview, $this> */
    public function latestReview(): HasOne
    {
        return $this->hasOne(SubmissionReview::class)->latestOfMany();
    }

    /** @return HasMany<SubmissionDecision, $this> */
    public function decisions(): HasMany
    {
        return $this->hasMany(SubmissionDecision::class);
    }

    /** @return HasOne<SubmissionDecision, $this> */
    public function latestDecision(): HasOne
    {
        return $this->hasOne(SubmissionDecision::class)->latestOfMany();
    }

    /** @return HasOne<IncomingLetter, $this> */
    public function incomingLetter(): HasOne
    {
        return $this->hasOne(IncomingLetter::class);
    }

    public function isPubliclyEditable(): bool
    {
        return in_array($this->status, [
            SubmissionStatus::Draft,
            SubmissionStatus::RevisionRequired,
        ], true);
    }

    /**
     * @param  Builder<LetterSubmission>  $query
     * @return Builder<LetterSubmission>
     */
    public function scopeOwnedByPublicUser(Builder $query, User $user): Builder
    {
        return $query
            ->where('source', SubmissionSource::Online)
            ->where('submitted_by_user_id', $user->getKey());
    }
}
