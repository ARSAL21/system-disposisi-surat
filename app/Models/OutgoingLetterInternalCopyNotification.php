<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int $outgoing_letter_delivery_id
 * @property int $position_id
 * @property int $recipient_user_id
 * @property int $recipient_position_assignment_id
 * @property CarbonInterface|null $notified_at
 * @property CarbonInterface $created_at
 * @property-read OutgoingLetterDelivery $delivery
 * @property-read Position $position
 * @property-read User $recipientUser
 * @property-read PositionAssignment $recipientPositionAssignment
 */
final class OutgoingLetterInternalCopyNotification extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['notified_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('Notifikasi tembusan tidak dapat diubah.'));
        self::deleting(fn () => throw new LogicException('Notifikasi tembusan tidak dapat dihapus.'));
    }

    /** @return BelongsTo<OutgoingLetterDelivery, $this> */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetterDelivery::class, 'outgoing_letter_delivery_id');
    }

    /** @return BelongsTo<Position, $this> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function recipientPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'recipient_position_assignment_id');
    }
}
