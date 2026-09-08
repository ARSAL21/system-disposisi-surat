<?php

namespace App\Models;

use App\Enums\LetterResponseDossierStatus;
use App\Policies\LetterResponseDossierPolicy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $incoming_letter_id
 * @property LetterResponseDossierStatus $status
 * @property CarbonInterface|null $opened_at
 * @property CarbonInterface|null $finalized_at
 * @property CarbonInterface|null $fulfilled_at
 * @property int|null $finalized_by_user_id
 * @property int|null $finalized_by_position_assignment_id
 * @property-read IncomingLetter $incomingLetter
 * @property-read Collection<int, LetterResponseDocument> $documents
 * @property-read Collection<int, LetterResponseReview> $reviews
 * @property-read Collection<int, OutgoingLetter> $mandates
 */
#[UsePolicy(LetterResponseDossierPolicy::class)]
class LetterResponseDossier extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => LetterResponseDossierStatus::class,
            'opened_at' => 'datetime',
            'finalized_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LetterResponseDossier $dossier): void {
            $dossier->public_id ??= (string) Str::ulid();

            if ($dossier->status !== LetterResponseDossierStatus::Open
                || $dossier->opened_at === null
                || $dossier->finalized_at !== null
                || $dossier->finalized_by_user_id !== null
                || $dossier->finalized_by_position_assignment_id !== null
                || $dossier->fulfilled_at !== null
                || $dossier->fulfilled_by_user_id !== null
                || $dossier->fulfilled_by_position_assignment_id !== null) {
                throw new LogicException('A response dossier must start as a clean OPEN dossier.');
            }
        });

        static::updating(function (LetterResponseDossier $dossier): void {
            $from = LetterResponseDossierStatus::tryFrom((string) $dossier->getRawOriginal('status'));
            $allowed = match ([$from, $dossier->status]) {
                [LetterResponseDossierStatus::Open, LetterResponseDossierStatus::Finalized] => [
                    'status', 'finalized_at', 'finalized_by_user_id',
                    'finalized_by_position_assignment_id', 'updated_at',
                ],
                [LetterResponseDossierStatus::Finalized, LetterResponseDossierStatus::Fulfilled] => [
                    'status', 'fulfilled_at', 'fulfilled_by_user_id',
                    'fulfilled_by_position_assignment_id', 'updated_at',
                ],
                default => [],
            };

            $hasRequiredContext = match ($dossier->status) {
                LetterResponseDossierStatus::Finalized => $dossier->finalized_at !== null
                    && $dossier->finalized_by_user_id !== null
                    && $dossier->finalized_by_position_assignment_id !== null,
                LetterResponseDossierStatus::Fulfilled => $dossier->fulfilled_at !== null
                    && $dossier->fulfilled_by_user_id !== null
                    && $dossier->fulfilled_by_position_assignment_id !== null,
                default => false,
            };

            if ($allowed === []
                || ! $hasRequiredContext
                || array_diff(array_keys($dossier->getDirty()), $allowed) !== []) {
                throw new LogicException('Invalid response dossier transition.');
            }
        });

        static::deleting(fn () => throw new LogicException('Response dossiers cannot be deleted.'));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<IncomingLetter, $this> */
    public function incomingLetter(): BelongsTo
    {
        return $this->belongsTo(IncomingLetter::class);
    }

    /** @return HasMany<LetterResponseDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(LetterResponseDocument::class);
    }

    /** @return HasMany<LetterResponseReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(LetterResponseReview::class);
    }

    /** @return HasMany<OutgoingLetter, $this> */
    public function mandates(): HasMany
    {
        return $this->hasMany(OutgoingLetter::class);
    }

    /** @return BelongsTo<User, $this> */
    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function finalizedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'finalized_by_position_assignment_id');
    }

    /** @return BelongsTo<User, $this> */
    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function fulfilledByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'fulfilled_by_position_assignment_id');
    }
}
