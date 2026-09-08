<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class LetterResponsePositionContextConflict extends RuntimeException
{
    public static function missing(): self
    {
        return new self('Penugasan aktif untuk tindakan balasan tidak lagi tersedia.');
    }

    public static function ambiguous(): self
    {
        return new self('Terdapat lebih dari satu penugasan aktif untuk Position tindakan balasan.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], JsonResponse::HTTP_CONFLICT);
    }
}
