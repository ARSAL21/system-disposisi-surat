<?php

namespace App\Exceptions;

use App\Enums\SubmissionStatus;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class SubmissionStateConflict extends RuntimeException
{
    public static function expectedDraft(SubmissionStatus $actualStatus): self
    {
        return new self("Submission must be in DRAFT state; current state is {$actualStatus->value}.");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
