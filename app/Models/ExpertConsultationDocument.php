<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ExpertConsultationDocument extends Model
{
    public const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['version_number' => 'integer', 'size_bytes' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Expert consultation documents are immutable.'));
        static::deleting(fn () => throw new LogicException('Expert consultation documents cannot be deleted.'));
    }

    /** @return BelongsTo<ExpertConsultation, $this> */
    public function expertConsultation(): BelongsTo
    {
        return $this->belongsTo(ExpertConsultation::class);
    }

    /** @return BelongsTo<ExpertConsultationDocument, $this> */
    public function replacesDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /** @return BelongsTo<PositionAssignment, $this> */
    public function uploadedByPositionAssignment(): BelongsTo
    {
        return $this->belongsTo(PositionAssignment::class, 'uploaded_by_position_assignment_id');
    }
}
