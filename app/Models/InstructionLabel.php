<?php

namespace App\Models;

use App\Policies\InstructionLabelPolicy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LogicException;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, Disposition> $dispositions
 */
#[UsePolicy(InstructionLabelPolicy::class)]
class InstructionLabel extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (InstructionLabel $label): void {
            if ($label->isDirty('code')) {
                throw new LogicException('Instruction label codes are immutable.');
            }
        });

        static::deleting(
            fn () => throw new LogicException('Instruction labels must be deactivated and cannot be deleted.'),
        );
    }

    /** @return BelongsToMany<Disposition, $this, DispositionInstructionLabel> */
    public function dispositions(): BelongsToMany
    {
        return $this->belongsToMany(Disposition::class, 'disposition_instruction_label')
            ->using(DispositionInstructionLabel::class);
    }
}
