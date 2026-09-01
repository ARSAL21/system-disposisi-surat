<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class DispositionPositionContextConflict extends RuntimeException
{
    public static function missing(): self
    {
        return new self('Penugasan aktif pada jabatan sumber tidak lagi tersedia.');
    }

    public static function ambiguous(): self
    {
        return new self('Ditemukan lebih dari satu penugasan aktif pada jabatan sumber.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
