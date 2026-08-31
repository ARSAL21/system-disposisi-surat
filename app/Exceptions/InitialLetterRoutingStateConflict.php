<?php

namespace App\Exceptions;

use App\Enums\IncomingLetterStatus;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class InitialLetterRoutingStateConflict extends RuntimeException
{
    public static function expectedRegistered(IncomingLetterStatus $actualStatus): self
    {
        return new self("Routing awal hanya dapat dibuat saat surat berstatus REGISTERED; status saat ini {$actualStatus->value}.");
    }

    public static function alreadyExists(): self
    {
        return new self('Surat sudah memiliki routing awal dan tidak dapat diarahkan ulang pada MVP.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
