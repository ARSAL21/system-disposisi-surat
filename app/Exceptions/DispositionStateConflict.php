<?php

namespace App\Exceptions;

use App\Exceptions\Concerns\RendersInertiaConflict;
use RuntimeException;

class DispositionStateConflict extends RuntimeException
{
    use RendersInertiaConflict;

    public static function staleSource(): self
    {
        return new self('Surat atau routing sumber telah berubah dan disposisi tidak dapat dibuat.');
    }

    public static function alreadyExists(): self
    {
        return new self('Routing ini sudah memiliki disposisi pertama.');
    }

    public static function staleBranch(): self
    {
        return new self('Status surat atau cabang disposisi telah berubah. Muat ulang halaman sebelum melanjutkan.');
    }

    public static function inconsistentGraph(): self
    {
        return new self('Graph disposisi tidak konsisten sehingga penanganan cabang dihentikan.');
    }
}
