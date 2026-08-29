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

    public static function expectedPubliclyEditable(SubmissionStatus $actualStatus): self
    {
        return new self("Submission must be in DRAFT or REVISION_REQUIRED state; current state is {$actualStatus->value}.");
    }

    public static function expectedSubmitted(SubmissionStatus $actualStatus): self
    {
        return new self("Submission must be in SUBMITTED state; current state is {$actualStatus->value}.");
    }

    public static function expectedReadyForApproval(SubmissionStatus $actualStatus): self
    {
        return new self("Submission must be in READY_FOR_APPROVAL state; current state is {$actualStatus->value}.");
    }

    public static function expectedScreenable(SubmissionStatus $actualStatus): self
    {
        return new self("Submission must be in SUBMITTED or INTERNAL_REVISION_REQUIRED state; current state is {$actualStatus->value}.");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
