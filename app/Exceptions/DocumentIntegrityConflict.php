<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class DocumentIntegrityConflict extends RuntimeException
{
    public static function unavailable(): self
    {
        return new self('Dokumen sumber tidak tersedia pada penyimpanan privat.');
    }

    public static function fingerprintMismatch(): self
    {
        return new self('Integritas dokumen tidak dapat diverifikasi. Registrasi dibatalkan.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
