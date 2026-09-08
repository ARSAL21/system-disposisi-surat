<?php

namespace App\Models;

use App\Enums\LetterResponseDocumentKind;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $letter_response_dossier_id
 * @property LetterResponseDocumentKind $kind
 * @property int $owner_position_id
 * @property int|null $source_recipient_id
 * @property int $created_by_user_id
 * @property int $created_by_position_assignment_id
 * @property CarbonInterface $created_at
 * @property-read LetterResponseDossier $dossier
 * @property-read Position $ownerPosition
 * @property-read DispositionRecipient|null $sourceRecipient
 * @property-read Collection<int, LetterResponseDocumentVersion> $versions
 */
class LetterResponseDocument extends Model
{
    public const UPDATED_AT = null;

    /** @return array<string, class-string<LetterResponseDocumentKind>> */
    protected function casts(): array
    {
        return ['kind' => LetterResponseDocumentKind::class];
    }

    protected static function booted(): void
    {
        static::creating(fn (LetterResponseDocument $document) => $document->public_id ??= (string) Str::ulid());
        static::updating(fn () => throw new LogicException('Response document series are immutable.'));
        static::deleting(fn () => throw new LogicException('Response document series cannot be deleted.'));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<LetterResponseDossier, $this> */
    public function dossier(): BelongsTo
    {
        return $this->belongsTo(LetterResponseDossier::class, 'letter_response_dossier_id');
    }

    /** @return BelongsTo<Position, $this> */
    public function ownerPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'owner_position_id');
    }

    /** @return BelongsTo<DispositionRecipient, $this> */
    public function sourceRecipient(): BelongsTo
    {
        return $this->belongsTo(DispositionRecipient::class, 'source_recipient_id');
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

    /** @return HasMany<LetterResponseDocumentVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(LetterResponseDocumentVersion::class)
            ->orderBy('version_number');
    }
}
