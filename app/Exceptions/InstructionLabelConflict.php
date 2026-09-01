<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class InstructionLabelConflict extends RuntimeException
{
    public static function actorUnavailable(): self
    {
        return new self('Akun pengelola tidak lagi aktif atau terverifikasi.');
    }

    public static function lastActive(): self
    {
        return new self('Label instruksi aktif terakhir tidak dapat dinonaktifkan.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
