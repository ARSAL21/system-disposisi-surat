<?php

namespace App\Models;

use App\Enums\IncomingLetterStatus;
use App\Policies\IncomingLetterPolicy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $letter_submission_id
 * @property string $agenda_number
 * @property int $agenda_year
 * @property int $sender_organization_id
 * @property string|null $external_letter_number
 * @property CarbonInterface|null $external_letter_date
 * @property string $subject
 * @property string|null $summary
 * @property CarbonInterface $received_at
 * @property IncomingLetterStatus $status
 * @property int $registered_by_user_id
 * @property int|null $registered_by_position_assignment_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read LetterSubmission $submission
 * @property-read SenderOrganization $senderOrganization
 * @property-read User $registeredBy
 * @property-read PositionAssignment|null $registeredByPositionAssignment
 * @property-read Collection<int, LetterDocument> $documents
 * @property-read LetterDocument|null $initialDocument
 */
#[UsePolicy(IncomingLetterPolicy::class)]
class IncomingLetter extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'agenda_year' => 'integer',
            'external_letter_date' => 'date',
            'received_at' => 'datetime',
            'status' => IncomingLetterStatus::class,
        ];
    }

    /** @return BelongsTo<LetterSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(LetterSubmission::class, 'letter_submission_id');
    }

    /** @return BelongsTo<SenderOrganization, $this> */
    public function senderOrganization(): BelongsTo
    {
        return $this->belongsTo(SenderOrganization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function registeredByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'registered_by_position_assignment_id');
    }

    /** @return HasMany<LetterDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(LetterDocument::class);
    }

    /**
     * Alias used by Laravel scoped nested binding for the
     * `{letterDocument}` route parameter.
     *
     * @return HasMany<LetterDocument, $this>
     */
    public function letterDocuments(): HasMany
    {
        return $this->documents();
    }

    /** @return HasOne<LetterDocument, $this> */
    public function initialDocument(): HasOne
    {
        return $this->hasOne(LetterDocument::class)
            ->where('version_number', 1);
    }

    /** @return HasOne<LetterDocument, $this> */
    public function currentDocument(): HasOne
    {
        return $this->hasOne(LetterDocument::class)
            ->ofMany('version_number', 'max');
    }
}
