<?php

namespace App\Exceptions;

use App\Exceptions\Concerns\RendersInertiaConflict;
use RuntimeException;

final class OutgoingLetterStateConflict extends RuntimeException
{
    use RendersInertiaConflict;

    public static function stale(): self
    {
        return new self('Tahap penerbitan surat telah berubah. Muat ulang halaman sebelum melanjutkan.');
    }

    public static function invalidGraph(): self
    {
        return new self('Mandat surat keluar tidak terhubung dengan dossier balasan yang valid.');
    }

    public static function missingDocument(): self
    {
        return new self('Dokumen final surat keluar belum tersedia atau tidak lagi menjadi versi terkini.');
    }

    public static function lastActiveMandate(): self
    {
        return new self('Mandat aktif terakhir pada rencana yang sudah difinalisasi tidak dapat ditarik.');
    }
}
