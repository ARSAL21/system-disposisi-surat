<?php

namespace App\Models;

use App\Enums\OutgoingLetterElectronicApprovalMethod;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $outgoing_letter_id
 * @property OutgoingLetterElectronicApprovalMethod $method
 * @property string $source_document_sha256
 * @property int|null $final_document_version_id
 * @property string|null $verification_token_hash
 * @property int $approved_by_user_id
 * @property int $approved_by_position_assignment_id
 * @property CarbonInterface $approved_at
 */
final class OutgoingLetterElectronicApproval extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'method' => OutgoingLetterElectronicApprovalMethod::class,
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::creating(fn (self $approval) => $approval->public_id ??= (string) Str::ulid());
        self::updating(fn () => throw new LogicException('Pengesahan elektronik surat keluar bersifat tetap.'));
        self::deleting(fn () => throw new LogicException('Pengesahan elektronik surat keluar tidak dapat dihapus.'));
    }

    /** @return BelongsTo<OutgoingLetter, $this> */
    public function outgoingLetter(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetter::class);
    }

    /** @return BelongsTo<OutgoingLetterDocumentVersion, $this> */
    public function finalDocumentVersion(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetterDocumentVersion::class, 'final_document_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function approvedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'approved_by_position_assignment_id');
    }
}
