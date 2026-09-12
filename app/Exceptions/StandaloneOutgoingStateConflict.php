<?php

namespace App\Exceptions;

use App\Exceptions\Concerns\RendersInertiaConflict;
use RuntimeException;

final class StandaloneOutgoingStateConflict extends RuntimeException
{
    use RendersInertiaConflict;

    public static function stale(): self
    {
        return new self('Status konsep surat telah berubah. Muat ulang halaman sebelum melanjutkan.');
    }

    public static function invalidTemplate(): self
    {
        return new self('Template surat tidak aktif atau tidak sesuai dengan Bagian penyusun.');
    }

    public static function duplicateDocument(): self
    {
        return new self('Versi PDF yang sama sudah tercatat pada konsep surat ini.');
    }

    public static function invalidCopyRecipient(): self
    {
        return new self('Penerima tembusan harus jabatan struktural aktif yang valid.');
    }

    public static function pdfNotStampable(): self
    {
        return new self('PDF tidak kompatibel untuk pengesahan QR. Ekspor ulang PDF tanpa enkripsi atau proteksi lalu unggah kembali.');
    }
}
