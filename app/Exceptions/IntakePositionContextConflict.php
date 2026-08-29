<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class IntakePositionContextConflict extends RuntimeException
{
    public static function missing(): self
    {
        return new self('An active GENERAL_AFFAIRS position assignment is required.');
    }

    public static function ambiguous(): self
    {
        return new self('More than one active GENERAL_AFFAIRS position assignment was found.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
