<?php

namespace App\Models;

use App\Auditing\AuditLogBuilder;
use App\Exceptions\AuditLogMutationDenied;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $actor_user_id
 * @property int|null $actor_position_assignment_id
 * @property string $action
 * @property string $subject_type
 * @property int|null $subject_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property array<string, mixed>|null $metadata
 * @property string|null $request_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property-read User|null $actor
 * @property-read PositionAssignment|null $actorPositionAssignment
 */
#[UseEloquentBuilder(AuditLogBuilder::class)]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @param Builder<static> $query */
    final protected function performUpdate(Builder $query): bool
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    final protected function performDeleteOnModel(): void
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function actorPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'actor_position_assignment_id');
    }
}
