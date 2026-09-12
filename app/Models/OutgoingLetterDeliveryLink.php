<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $outgoing_letter_delivery_id
 * @property string $token_hash
 * @property string $recipient_email
 * @property CarbonInterface $sent_at
 * @property CarbonInterface $expires_at
 * @property int $created_by_user_id
 * @property int $created_by_position_assignment_id
 * @property CarbonInterface $created_at
 * @property-read OutgoingLetterDelivery $delivery
 * @property-read OutgoingLetterDeliveryLinkRevocation|null $revocation
 */
final class OutgoingLetterDeliveryLink extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        self::creating(fn (self $link) => $link->public_id ??= (string) Str::ulid());
        self::updating(fn () => throw new LogicException('Tautan pengiriman surat tidak dapat diubah.'));
        self::deleting(fn () => throw new LogicException('Tautan pengiriman surat tidak dapat dihapus.'));
    }

    /** @return BelongsTo<OutgoingLetterDelivery, $this> */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetterDelivery::class, 'outgoing_letter_delivery_id');
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

    /** @return HasOne<OutgoingLetterDeliveryLinkRevocation, $this> */
    public function revocation(): HasOne
    {
        return $this->hasOne(OutgoingLetterDeliveryLinkRevocation::class);
    }
}
