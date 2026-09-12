<?php

namespace App\Models;

use App\Enums\StandaloneOutgoingDraftStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $organizational_unit_id
 * @property int $outgoing_letter_template_version_id
 * @property string $recipient_name
 * @property string|null $recipient_organization
 * @property string|null $recipient_position
 * @property string|null $recipient_address
 * @property string|null $recipient_email
 * @property string $subject
 * @property string|null $summary
 * @property StandaloneOutgoingDraftStatus $status
 * @property CarbonInterface|null $submitted_at
 * @property int $created_by_user_id
 * @property int $created_by_position_assignment_id
 * @property int|null $corrects_outgoing_letter_id
 * @property string|null $correction_reason
 * @property-read OrganizationalUnit $organizationalUnit
 * @property-read OutgoingLetterTemplateVersion $templateVersion
 * @property-read Collection<int, StandaloneOutgoingDocumentVersion> $documentVersions
 * @property-read Collection<int, StandaloneOutgoingReview> $reviews
 */
class StandaloneOutgoingDraft extends Model
{
    protected function casts(): array
    {
        return [
            'status' => StandaloneOutgoingDraftStatus::class,
            'submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $draft) => $draft->public_id ??= (string) Str::ulid());
        static::updating(function (self $draft): void {
            $from = StandaloneOutgoingDraftStatus::tryFrom((string) $draft->getRawOriginal('status'));
            $to = $draft->status;
            $dirty = array_keys($draft->getDirty());
            $editable = [
                'outgoing_letter_template_version_id', 'recipient_name', 'recipient_organization',
                'recipient_position', 'recipient_address', 'recipient_email', 'subject', 'summary', 'updated_at',
            ];

            if ($from === $to && in_array($to, [StandaloneOutgoingDraftStatus::Draft, StandaloneOutgoingDraftStatus::RevisionRequired], true)
                && array_diff($dirty, $editable) === []) {
                return;
            }

            $allowed = match ([$from, $to]) {
                [StandaloneOutgoingDraftStatus::Draft, StandaloneOutgoingDraftStatus::SectionReview],
                [StandaloneOutgoingDraftStatus::RevisionRequired, StandaloneOutgoingDraftStatus::SectionReview] => ['status', 'submitted_at', 'updated_at'],
                [StandaloneOutgoingDraftStatus::SectionReview, StandaloneOutgoingDraftStatus::AssistantReview],
                [StandaloneOutgoingDraftStatus::AssistantReview, StandaloneOutgoingDraftStatus::AwaitingNumber],
                [StandaloneOutgoingDraftStatus::AwaitingNumber, StandaloneOutgoingDraftStatus::NumberAssigned],
                [StandaloneOutgoingDraftStatus::NumberAssigned, StandaloneOutgoingDraftStatus::SekdaReview],
                [StandaloneOutgoingDraftStatus::SekdaReview, StandaloneOutgoingDraftStatus::AwaitingManualSignature],
                [StandaloneOutgoingDraftStatus::SekdaReview, StandaloneOutgoingDraftStatus::ReadyForDelivery],
                [StandaloneOutgoingDraftStatus::SekdaReview, StandaloneOutgoingDraftStatus::RevisionRequired],
                [StandaloneOutgoingDraftStatus::AwaitingManualSignature, StandaloneOutgoingDraftStatus::ManualScanReview],
                [StandaloneOutgoingDraftStatus::ManualScanReview, StandaloneOutgoingDraftStatus::ReadyForDelivery],
                [StandaloneOutgoingDraftStatus::ManualScanReview, StandaloneOutgoingDraftStatus::RevisionRequired],
                [StandaloneOutgoingDraftStatus::AssistantReview, StandaloneOutgoingDraftStatus::SekdaReview],
                [StandaloneOutgoingDraftStatus::SectionReview, StandaloneOutgoingDraftStatus::RevisionRequired],
                [StandaloneOutgoingDraftStatus::AssistantReview, StandaloneOutgoingDraftStatus::RevisionRequired] => ['status', 'updated_at'],
                default => [],
            };

            if ($allowed === [] || array_diff($dirty, $allowed) !== []) {
                throw new LogicException('Invalid standalone outgoing draft lifecycle transition.');
            }
        });
        static::deleting(fn () => throw new LogicException('Draf surat keluar yang tercatat tidak dapat dihapus.'));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<OrganizationalUnit, $this> */
    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    /** @return BelongsTo<OutgoingLetterTemplateVersion, $this> */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetterTemplateVersion::class, 'outgoing_letter_template_version_id');
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

    /** @return HasMany<StandaloneOutgoingCopyRecipient, $this> */
    public function copyRecipients(): HasMany
    {
        return $this->hasMany(StandaloneOutgoingCopyRecipient::class)->orderBy('id');
    }

    /** @return HasMany<StandaloneOutgoingDocumentVersion, $this> */
    public function documentVersions(): HasMany
    {
        return $this->hasMany(StandaloneOutgoingDocumentVersion::class)->orderBy('version_number');
    }

    /** @return HasMany<StandaloneOutgoingDocumentVersion, $this> */
    public function standaloneOutgoingDocumentVersions(): HasMany
    {
        return $this->documentVersions();
    }

    /** @return HasOne<StandaloneOutgoingDocumentVersion, $this> */
    public function currentDocumentVersion(): HasOne
    {
        return $this->hasOne(StandaloneOutgoingDocumentVersion::class)->ofMany('version_number', 'max');
    }

    /** @return HasMany<StandaloneOutgoingReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(StandaloneOutgoingReview::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return HasOne<OutgoingLetter, $this> */
    public function outgoingLetter(): HasOne
    {
        return $this->hasOne(OutgoingLetter::class, 'standalone_outgoing_draft_id');
    }

    /** @return BelongsTo<OutgoingLetter, $this> */
    public function correctionSource(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetter::class, 'corrects_outgoing_letter_id');
    }
}
