<?php

namespace App\Models;

use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property OutgoingLetterOrigin $origin
 * @property int|null $incoming_letter_id
 * @property int|null $letter_response_dossier_id
 * @property int|null $standalone_outgoing_draft_id
 * @property int|null $source_document_version_id
 * @property int|null $corrects_outgoing_letter_id
 * @property int $signatory_position_id
 * @property string $subject
 * @property OutgoingLetterStatus $status
 * @property string|null $outgoing_number
 * @property int|null $agenda_year
 * @property CarbonInterface|null $letter_date
 * @property CarbonInterface|null $numbered_at
 * @property string|null $withdrawal_reason
 * @property CarbonInterface|null $withdrawn_at
 * @property int $authorized_by_user_id
 * @property int $authorized_by_position_assignment_id
 * @property CarbonInterface $authorized_at
 * @property-read IncomingLetter|null $incomingLetter
 * @property-read LetterResponseDossier|null $dossier
 * @property-read StandaloneOutgoingDraft|null $standaloneDraft
 * @property-read LetterResponseDocumentVersion|null $sourceDocumentVersion
 * @property-read Position $signatoryPosition
 * @property-read User $authorizedBy
 * @property-read PositionAssignment $authorizedByPositionAssignment
 * @property-read User|null $numberedBy
 * @property-read User|null $withdrawnBy
 * @property-read Collection<int, OutgoingLetterDocumentVersion> $documentVersions
 * @property-read OutgoingLetterDocumentVersion|null $currentDocumentVersion
 * @property-read OutgoingLetterDelivery|null $delivery
 */
class OutgoingLetter extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => OutgoingLetterStatus::class,
            'origin' => OutgoingLetterOrigin::class,
            'authorized_at' => 'datetime',
            'agenda_year' => 'integer',
            'letter_date' => 'date',
            'numbered_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OutgoingLetter $letter): void {
            $letter->public_id ??= (string) Str::ulid();
            $attributes = $letter->getAttributes();

            $origin = OutgoingLetterOrigin::tryFrom((string) ($attributes['origin'] ?? OutgoingLetterOrigin::Response->value));
            if ($origin === OutgoingLetterOrigin::Response
                && (($attributes['status'] ?? null) !== OutgoingLetterStatus::Authorized->value
                    || ($attributes['authorized_at'] ?? null) === null)) {
                throw new LogicException('Mandat balasan harus dimulai sebagai AUTHORIZED.');
            }

            if ($origin === OutgoingLetterOrigin::Standalone
                && (($attributes['status'] ?? null) !== OutgoingLetterStatus::NumberAssigned->value
                    || ($attributes['standalone_outgoing_draft_id'] ?? null) === null
                    || ($attributes['outgoing_number'] ?? null) === null
                    || ($attributes['agenda_year'] ?? null) === null
                    || ($attributes['letter_date'] ?? null) === null
                    || ($attributes['numbered_by_user_id'] ?? null) === null
                    || ($attributes['numbered_by_position_assignment_id'] ?? null) === null
                    || ($attributes['numbered_at'] ?? null) === null)) {
                throw new LogicException('Surat mandiri harus dibuat bersama nomor surat resmi.');
            }
        });
        static::updating(function (OutgoingLetter $letter): void {
            $from = OutgoingLetterStatus::tryFrom((string) $letter->getRawOriginal('status'));
            $to = $letter->status;
            $dirty = array_keys($letter->getDirty());

            if ($letter->origin === OutgoingLetterOrigin::Standalone) {
                $allowed = match ([$from, $to]) {
                    [OutgoingLetterStatus::NumberAssigned, OutgoingLetterStatus::SekdaReview],
                    [OutgoingLetterStatus::SekdaReview, OutgoingLetterStatus::AwaitingManualSignature],
                    [OutgoingLetterStatus::SekdaReview, OutgoingLetterStatus::ReadyForDelivery],
                    [OutgoingLetterStatus::SekdaReview, OutgoingLetterStatus::RevisionRequired],
                    [OutgoingLetterStatus::AwaitingManualSignature, OutgoingLetterStatus::ManualScanReview],
                    [OutgoingLetterStatus::ManualScanReview, OutgoingLetterStatus::ReadyForDelivery],
                    [OutgoingLetterStatus::ManualScanReview, OutgoingLetterStatus::RevisionRequired],
                    [OutgoingLetterStatus::ReadyForDelivery, OutgoingLetterStatus::Delivered],
                    [OutgoingLetterStatus::RevisionRequired, OutgoingLetterStatus::SekdaReview] => ['status', 'updated_at'],
                    default => [],
                };

                if ($allowed === [] || array_diff($dirty, $allowed) !== []) {
                    throw new LogicException('Invalid standalone outgoing letter lifecycle transition.');
                }

                return;
            }

            $allowed = match ([$from, $to]) {
                [OutgoingLetterStatus::Authorized, OutgoingLetterStatus::NumberAssigned] => [
                    'status', 'outgoing_number', 'agenda_year', 'letter_date', 'numbered_by_user_id',
                    'numbered_by_position_assignment_id', 'numbered_at', 'updated_at',
                ],
                [OutgoingLetterStatus::Authorized, OutgoingLetterStatus::Withdrawn] => [
                    'status', 'withdrawal_reason', 'withdrawn_by_user_id',
                    'withdrawn_by_position_assignment_id', 'withdrawn_at', 'updated_at',
                ],
                [OutgoingLetterStatus::NumberAssigned, OutgoingLetterStatus::SignedDocumentUploaded],
                [OutgoingLetterStatus::SignedDocumentUploaded, OutgoingLetterStatus::AdminVerified],
                [OutgoingLetterStatus::AdminVerified, OutgoingLetterStatus::Delivered] => ['status', 'updated_at'],
                default => [],
            };

            $hasRequiredContext = match ($to) {
                OutgoingLetterStatus::NumberAssigned => $letter->outgoing_number !== null
                    && $letter->agenda_year !== null
                    && $letter->letter_date !== null
                    && $letter->numbered_by_user_id !== null
                    && $letter->numbered_by_position_assignment_id !== null
                    && $letter->numbered_at !== null,
                OutgoingLetterStatus::Withdrawn => $letter->withdrawal_reason !== null
                    && $letter->withdrawn_by_user_id !== null
                    && $letter->withdrawn_by_position_assignment_id !== null
                    && $letter->withdrawn_at !== null
                    && $letter->outgoing_number === null,
                OutgoingLetterStatus::SignedDocumentUploaded,
                OutgoingLetterStatus::AdminVerified,
                OutgoingLetterStatus::Delivered => true,
                default => false,
            };

            if ($allowed === [] || ! $hasRequiredContext || array_diff($dirty, $allowed) !== []) {
                throw new LogicException('Invalid outgoing letter lifecycle transition.');
            }
        });
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

    /** @return BelongsTo<StandaloneOutgoingDraft, $this> */
    public function standaloneDraft(): BelongsTo
    {
        return $this->belongsTo(StandaloneOutgoingDraft::class, 'standalone_outgoing_draft_id');
    }

    /** @return HasManyThrough<StandaloneOutgoingDocumentVersion, StandaloneOutgoingDraft, $this> */
    public function standaloneDocuments(): HasManyThrough
    {
        return $this->hasManyThrough(
            StandaloneOutgoingDocumentVersion::class,
            StandaloneOutgoingDraft::class,
            'id',
            'standalone_outgoing_draft_id',
            'standalone_outgoing_draft_id',
            'id',
        );
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

    /** @return BelongsTo<User, $this> */
    public function numberedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'numbered_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function numberedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'numbered_by_position_assignment_id');
    }

    /** @return BelongsTo<User, $this> */
    public function withdrawnBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withdrawn_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function withdrawnByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'withdrawn_by_position_assignment_id');
    }

    /** @return BelongsTo<self, $this> */
    public function correctsOutgoingLetter(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrects_outgoing_letter_id');
    }

    /** @return HasMany<StandaloneOutgoingDraft, $this> */
    public function correctionDrafts(): HasMany
    {
        return $this->hasMany(StandaloneOutgoingDraft::class, 'corrects_outgoing_letter_id');
    }

    /** @return HasMany<OutgoingLetterDocumentVersion, $this> */
    public function documentVersions(): HasMany
    {
        return $this->hasMany(OutgoingLetterDocumentVersion::class)->orderBy('version_number');
    }

    /** @return HasMany<OutgoingLetterDocumentVersion, $this> */
    public function outgoingLetterDocumentVersions(): HasMany
    {
        return $this->documentVersions();
    }

    /** @return HasOne<OutgoingLetterDocumentVersion, $this> */
    public function currentDocumentVersion(): HasOne
    {
        return $this->hasOne(OutgoingLetterDocumentVersion::class)->ofMany('version_number', 'max');
    }

    /** @return HasOne<OutgoingLetterDelivery, $this> */
    public function delivery(): HasOne
    {
        return $this->hasOne(OutgoingLetterDelivery::class);
    }

    /** @return HasOne<OutgoingLetterElectronicApproval, $this> */
    public function electronicApproval(): HasOne
    {
        return $this->hasOne(OutgoingLetterElectronicApproval::class)->latestOfMany('approved_at');
    }

    /** @return HasMany<StandaloneOutgoingSekdaDecision, $this> */
    public function sekdaDecisions(): HasMany
    {
        return $this->hasMany(StandaloneOutgoingSekdaDecision::class)->orderBy('created_at')->orderBy('id');
    }
}
