<?php

namespace App\Models;

use App\Enums\AccountType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $normalized_email
 * @property string $email
 * @property AccountType $account_type
 * @property string|null $phone_number
 * @property string $token_hash
 * @property CarbonInterface $expires_at
 * @property CarbonInterface|null $accepted_at
 * @property CarbonInterface|null $revoked_at
 * @property int $invited_by_user_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read User $invitedBy
 */
#[Fillable([
    'public_id',
    'name',
    'email',
    'normalized_email',
    'account_type',
    'phone_number',
    'token_hash',
    'expires_at',
    'accepted_at',
    'revoked_at',
    'invited_by_user_id',
])]
class UserInvitation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'account_type' => AccountType::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * @return HasMany<UserAccountEvent, $this>
     */
    public function accountEvents(): HasMany
    {
        return $this->hasMany(UserAccountEvent::class, 'user_invitation_id');
    }

    public function getEmailAttribute(): string
    {
        return (string) ($this->attributes['normalized_email'] ?? '');
    }

    public function setEmailAttribute(string $value): void
    {
        $this->attributes['normalized_email'] = strtolower(trim($value));
    }

    public function isValid(string $rawToken): bool
    {
        return $this->isPending()
            && hash_equals($this->token_hash, hash('sha256', $rawToken));
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture();
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && $this->expires_at->isPast();
    }

    public function status(): string
    {
        if ($this->isAccepted()) {
            return 'ACCEPTED';
        }

        if ($this->isRevoked()) {
            return 'REVOKED';
        }

        if ($this->isExpired()) {
            return 'EXPIRED';
        }

        return 'PENDING';
    }
}
