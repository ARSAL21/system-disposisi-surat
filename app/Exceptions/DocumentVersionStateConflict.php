<?php

namespace App\Exceptions;

use App\Enums\IncomingLetterStatus;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class DocumentVersionStateConflict extends RuntimeException
{
    public static function expectedRegistered(IncomingLetterStatus $actualStatus): self
    {
        return new self("Versi dokumen hanya dapat dibuat saat surat berstatus REGISTERED; status saat ini {$actualStatus->value}.");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
