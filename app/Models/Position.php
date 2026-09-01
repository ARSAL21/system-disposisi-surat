<?php

namespace App\Models;

use App\Policies\PositionPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $position_level_id
 * @property int|null $organizational_unit_id
 * @property string $code
 * @property string $name
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PositionLevel $positionLevel
 * @property-read OrganizationalUnit|null $organizationalUnit
 * @property-read Collection<int, PositionAssignment> $assignments
 * @property-read PositionAssignment|null $activeAssignment
 * @property-read Collection<int, LetterRoute> $receivedLetterRoutes
 * @property-read Collection<int, DispositionRecipient> $receivedDispositionRecipients
 */
#[UsePolicy(PositionPolicy::class)]
class Position extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<PositionLevel, $this> */
    public function positionLevel(): BelongsTo
    {
        return $this->belongsTo(PositionLevel::class);
    }

    /** @return BelongsTo<OrganizationalUnit, $this> */
    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    /** @return HasMany<PositionAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(PositionAssignment::class);
    }

    /** @return HasOne<PositionAssignment, $this> */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(PositionAssignment::class)
            ->where('started_at', '<=', now())
            ->whereNull('ended_at');
    }

    /** @return HasMany<LetterRoute, $this> */
    public function receivedLetterRoutes(): HasMany
    {
        return $this->hasMany(LetterRoute::class, 'recipient_position_id');
    }

    /** @return HasMany<DispositionRecipient, $this> */
    public function receivedDispositionRecipients(): HasMany
    {
        return $this->hasMany(DispositionRecipient::class, 'recipient_position_id');
    }
}
