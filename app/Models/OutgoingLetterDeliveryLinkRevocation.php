<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

final class OutgoingLetterDeliveryLinkRevocation extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['revoked_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('Pencabutan tautan pengiriman tidak dapat diubah.'));
        self::deleting(fn () => throw new LogicException('Pencabutan tautan pengiriman tidak dapat dihapus.'));
    }

    /** @return BelongsTo<OutgoingLetterDeliveryLink, $this> */
    public function link(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetterDeliveryLink::class, 'outgoing_letter_delivery_link_id');
    }

    /** @return BelongsTo<User, $this> */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function revokedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'revoked_by_position_assignment_id');
    }
}
