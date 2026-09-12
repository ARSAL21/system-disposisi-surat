<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int $standalone_outgoing_draft_id
 * @property int $position_id
 * @property CarbonInterface $created_at
 */
class StandaloneOutgoingCopyRecipient extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Tembusan surat keluar tidak dapat diubah langsung.'));
    }

    /** @return BelongsTo<StandaloneOutgoingDraft, $this> */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(StandaloneOutgoingDraft::class, 'standalone_outgoing_draft_id');
    }

    /** @return BelongsTo<Position, $this> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
