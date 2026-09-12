<?php

namespace App\Models;

use App\Enums\UserAccountEventType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $user_invitation_id
 * @property string|null $user_name
 * @property string|null $user_email
 * @property UserAccountEventType $event_type
 * @property int|null $actor_user_id
 * @property string $description
 * @property string|null $reason
 * @property array<string, mixed>|null $metadata
 * @property CarbonInterface $created_at
 * @property-read User|null $user
 * @property-read User|null $actor
 * @property-read UserInvitation|null $invitation
 */
#[Fillable([
    'user_id',
    'user_invitation_id',
    'user_name',
    'user_email',
    'event_type',
    'actor_user_id',
    'description',
    'reason',
    'metadata',
    'created_at',
])]
class UserAccountEvent extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => UserAccountEventType::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            if ($event->getAttribute('created_at') === null) {
                $event->created_at = now();
            }
        });

        static::updating(function (): void {
            throw new LogicException('UserAccountEvent records are append-only and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new LogicException('UserAccountEvent records are append-only and cannot be deleted.');
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * @return BelongsTo<UserInvitation, $this>
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(UserInvitation::class, 'user_invitation_id');
    }
}
