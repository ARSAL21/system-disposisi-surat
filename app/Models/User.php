<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Authorization\AuthorizationCatalog;
use App\Enums\AccountType;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property CarbonInterface|null $email_verified_at
 * @property AccountType $account_type
 * @property bool $is_active
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonInterface|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, PositionAssignment> $positionAssignments
 * @property-read Collection<int, PositionAssignment> $activePositionAssignments
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected string $guard_name = AuthorizationCatalog::GUARD_NAME;

    protected function getDefaultGuardName(): string
    {
        return $this->guard_name;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'account_type' => AccountType::class,
            'is_active' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isPublicAccount(): bool
    {
        return $this->account_type === AccountType::PublicAccount;
    }

    public function isInternalAccount(): bool
    {
        return $this->account_type === AccountType::InternalAccount;
    }

    /** @return HasMany<LetterSubmission, $this> */
    public function letterSubmissions(): HasMany
    {
        return $this->hasMany(LetterSubmission::class, 'submitted_by_user_id');
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    /** @return HasMany<PositionAssignment, $this> */
    public function positionAssignments(): HasMany
    {
        return $this->hasMany(PositionAssignment::class);
    }

    /** @return HasMany<PositionAssignment, $this> */
    public function activePositionAssignments(): HasMany
    {
        return $this->positionAssignments()->whereNull('ended_at');
    }
}
