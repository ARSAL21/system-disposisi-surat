<?php

namespace App\Exceptions;

use App\Exceptions\Concerns\RendersInertiaConflict;
use RuntimeException;

class ExpertConsultationStateConflict extends RuntimeException
{
    use RendersInertiaConflict;

    public static function stale(): self
    {
        return new self('Status surat atau telaah telah berubah. Muat ulang halaman sebelum melanjutkan.');
    }

    public static function pending(): self
    {
        return new self('Surat belum dapat diteruskan karena masih ada telaah Staf Ahli yang menunggu hasil.');
    }

    public static function invalidHierarchy(): self
    {
        return new self('Jabatan Staf Ahli tidak sesuai dengan hubungan organisasi yang berlaku.');
    }
}
