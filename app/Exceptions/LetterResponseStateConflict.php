<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class LetterResponseStateConflict extends RuntimeException
{
    public static function staleDossier(): self
    {
        return new self('Status dossier balasan telah berubah. Muat ulang halaman sebelum melanjutkan.');
    }

    public static function incompleteSubtree(): self
    {
        return new self('Seluruh cabang Kepala Bagian di bawah Asisten harus selesai sebelum proposal dibuat.');
    }

    public static function invalidDocument(): self
    {
        return new self('Dokumen balasan tidak sesuai dengan dossier atau hierarchy yang berlaku.');
    }

    public static function invalidSignatory(): self
    {
        return new self('Penandatangan harus eksekutif penerima atau Asisten yang terlibat dalam surat ini.');
    }

    public static function missingMandate(): self
    {
        return new self('Rencana balasan hanya dapat difinalisasi setelah minimal satu mandat dibuat.');
    }

    public static function documentAlreadyMandated(): self
    {
        return new self('Dokumen yang sudah menjadi sumber mandat tidak dapat dikembalikan atau direvisi.');
    }

    public static function documentAlreadyUsed(): self
    {
        return new self('Versi dokumen sudah dipakai oleh dokumen tingkat berikutnya dan tidak dapat dikembalikan atau direvisi.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], JsonResponse::HTTP_CONFLICT);
    }
}
