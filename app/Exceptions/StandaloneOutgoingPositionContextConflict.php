<?php

namespace App\Exceptions;

use App\Exceptions\Concerns\RendersInertiaConflict;
use RuntimeException;

final class StandaloneOutgoingPositionContextConflict extends RuntimeException
{
    use RendersInertiaConflict;

    public static function missing(): self
    {
        return new self('Penugasan jabatan aktif untuk tahap surat keluar ini tidak ditemukan.');
    }

    public static function ambiguous(): self
    {
        return new self('Terdapat lebih dari satu penugasan aktif yang sama. Hubungi administrator organisasi.');
    }
}
