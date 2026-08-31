<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class DocumentVersionPositionContextConflict extends RuntimeException
{
    public static function missing(): self
    {
        return new self('Penugasan aktif sebagai Kepala Bagian Umum tidak lagi tersedia.');
    }

    public static function ambiguous(): self
    {
        return new self('Ditemukan lebih dari satu penugasan aktif sebagai Kepala Bagian Umum.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
