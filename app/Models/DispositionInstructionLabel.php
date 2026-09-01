<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use LogicException;

class DispositionInstructionLabel extends Pivot
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'disposition_instruction_label';

    protected static function booted(): void
    {
        static::updating(
            fn () => throw new LogicException('Disposition instruction snapshots cannot be updated.'),
        );

        static::deleting(
            fn () => throw new LogicException('Disposition instruction snapshots cannot be deleted.'),
        );
    }
}
