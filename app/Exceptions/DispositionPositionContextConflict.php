<?php

namespace App\Exceptions;

use App\Exceptions\Concerns\RendersInertiaConflict;
use RuntimeException;

class DispositionPositionContextConflict extends RuntimeException
{
    use RendersInertiaConflict;

    public static function missing(): self
    {
        return new self('Penugasan aktif pada jabatan sumber tidak lagi tersedia.');
    }

    public static function ambiguous(): self
    {
        return new self('Ditemukan lebih dari satu penugasan aktif pada jabatan sumber.');
    }
}
