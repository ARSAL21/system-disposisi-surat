<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class DispositionStateConflict extends RuntimeException
{
    public static function staleSource(): self
    {
        return new self('Surat atau routing sumber telah berubah dan disposisi tidak dapat dibuat.');
    }

    public static function alreadyExists(): self
    {
        return new self('Routing ini sudah memiliki disposisi pertama.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
