<?php

namespace App\Models;

use App\Enums\OutgoingDeliveryMethod;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $outgoing_letter_id
 * @property OutgoingDeliveryMethod $method
 * @property string|null $recipient_name
 * @property string|null $tracking_number
 * @property string|null $note
 * @property int $delivered_by_user_id
 * @property int $delivered_by_position_assignment_id
 * @property CarbonInterface $delivered_at
 * @property CarbonInterface $created_at
 * @property-read User $deliveredBy
 * @property-read PositionAssignment $deliveredByPositionAssignment
 */
final class OutgoingLetterDelivery extends Model
{
    public const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'method' => OutgoingDeliveryMethod::class,
            'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::creating(fn (self $delivery) => $delivery->public_id ??= (string) Str::ulid());
        self::updating(fn () => throw new LogicException('Outgoing deliveries are append-only.'));
        self::deleting(fn () => throw new LogicException('Outgoing deliveries cannot be deleted.'));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<OutgoingLetter, $this> */
    public function outgoingLetter(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetter::class);
    }

    /** @return BelongsTo<User, $this> */
    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function deliveredByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'delivered_by_position_assignment_id');
    }
}
