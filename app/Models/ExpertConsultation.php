<?php

namespace App\Models;

use App\Enums\ExpertConsultationStatus;
use App\Policies\ExpertConsultationPolicy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * @property int $id
 * @property int $incoming_letter_id
 * @property int $letter_route_id
 * @property int $expert_position_id
 * @property int $requested_by_user_id
 * @property int $requested_by_position_assignment_id
 * @property ExpertConsultationStatus $status
 * @property string|null $request_note
 * @property CarbonInterface $requested_at
 * @property CarbonInterface|null $reported_at
 * @property CarbonInterface|null $cancelled_at
 * @property int|null $cancelled_by_user_id
 * @property int|null $cancelled_by_position_assignment_id
 * @property string|null $cancellation_reason
 * @property-read IncomingLetter $incomingLetter
 * @property-read LetterRoute $letterRoute
 * @property-read Position $expertPosition
 * @property-read User $requestedBy
 * @property-read PositionAssignment $requestedByPositionAssignment
 * @property-read ExpertConsultationReport|null $report
 * @property-read Collection<int, ExpertConsultationDocument> $documents
 */
#[UsePolicy(ExpertConsultationPolicy::class)]
class ExpertConsultation extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ExpertConsultationStatus::class,
            'requested_at' => 'datetime',
            'reported_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (ExpertConsultation $consultation): void {
            $allowed = ['status', 'reported_at', 'cancelled_at', 'cancelled_by_user_id', 'cancelled_by_position_assignment_id', 'cancellation_reason', 'updated_at'];
            if (array_diff(array_keys($consultation->getDirty()), $allowed) !== []) {
                throw new LogicException('Expert consultation request fields are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Expert consultations cannot be deleted.'));
    }

    /** @return BelongsTo<IncomingLetter, $this> */
    public function incomingLetter(): BelongsTo
    {
        return $this->belongsTo(IncomingLetter::class);
    }

    /** @return BelongsTo<LetterRoute, $this> */
    public function letterRoute(): BelongsTo
    {
        return $this->belongsTo(LetterRoute::class);
    }

    /** @return BelongsTo<Position, $this> */
    public function expertPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'expert_position_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function requestedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'requested_by_position_assignment_id');
    }

    /** @return HasOne<ExpertConsultationReport, $this> */
    public function report(): HasOne
    {
        return $this->hasOne(ExpertConsultationReport::class);
    }

    /** @return HasMany<ExpertConsultationDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ExpertConsultationDocument::class)->orderBy('version_number');
    }
}
