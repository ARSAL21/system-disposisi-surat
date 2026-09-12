<?php

namespace App\Models;

use App\Enums\StandaloneOutgoingSekdaDecisionType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $outgoing_letter_id
 * @property StandaloneOutgoingSekdaDecisionType $decision
 * @property string|null $note
 * @property int $decided_by_user_id
 * @property int $decided_by_position_assignment_id
 * @property CarbonInterface $created_at
 */
final class StandaloneOutgoingSekdaDecision extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['decision' => StandaloneOutgoingSekdaDecisionType::class];
    }

    protected static function booted(): void
    {
        self::creating(fn (self $decision) => $decision->public_id ??= (string) Str::ulid());
        self::updating(fn () => throw new LogicException('Keputusan Sekda surat keluar bersifat append-only.'));
        self::deleting(fn () => throw new LogicException('Keputusan Sekda surat keluar tidak dapat dihapus.'));
    }

    /** @return BelongsTo<OutgoingLetter, $this> */
    public function outgoingLetter(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetter::class);
    }

    /** @return BelongsTo<User, $this> */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function decidedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'decided_by_position_assignment_id');
    }
}
