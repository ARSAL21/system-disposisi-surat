<?php

namespace App\Models;

use App\Enums\OutgoingLetterStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int|null $incoming_letter_id
 * @property int|null $letter_response_dossier_id
 * @property int $source_document_version_id
 * @property int $signatory_position_id
 * @property string $subject
 * @property OutgoingLetterStatus $status
 * @property int $authorized_by_user_id
 * @property int $authorized_by_position_assignment_id
 * @property CarbonInterface $authorized_at
 * @property-read LetterResponseDocumentVersion $sourceDocumentVersion
 * @property-read Position $signatoryPosition
 */
class OutgoingLetter extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => OutgoingLetterStatus::class,
            'authorized_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OutgoingLetter $letter): void {
            $letter->public_id ??= (string) Str::ulid();
            $attributes = $letter->getAttributes();

            if (($attributes['status'] ?? null) !== OutgoingLetterStatus::Authorized->value
                || ($attributes['authorized_at'] ?? null) === null) {
                throw new LogicException('M8.2 outgoing letters must start as AUTHORIZED mandates.');
            }
        });
        static::updating(fn () => throw new LogicException('Authorized mandates cannot change during M8.2.'));
        static::deleting(fn () => throw new LogicException('Outgoing letter mandates cannot be deleted.'));
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

    /** @return BelongsTo<LetterResponseDossier, $this> */
    public function dossier(): BelongsTo
    {
        return $this->belongsTo(LetterResponseDossier::class, 'letter_response_dossier_id');
    }

    /** @return BelongsTo<LetterResponseDocumentVersion, $this> */
    public function sourceDocumentVersion(): BelongsTo
    {
        return $this->belongsTo(LetterResponseDocumentVersion::class, 'source_document_version_id');
    }

    /** @return BelongsTo<Position, $this> */
    public function signatoryPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'signatory_position_id');
    }

    /** @return BelongsTo<User, $this> */
    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function authorizedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'authorized_by_position_assignment_id');
    }
}
