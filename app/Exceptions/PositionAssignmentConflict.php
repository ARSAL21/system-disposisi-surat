<?php

namespace App\Exceptions;

use App\Models\Position;
use App\Models\PositionAssignment;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PositionAssignmentConflict extends RuntimeException
{
    public static function positionOccupied(Position $position): self
    {
        return new self("Position [{$position->code}] already has an active assignment.");
    }

    public static function positionVacant(Position $position): self
    {
        return new self("Position [{$position->code}] does not have an active assignment to replace.");
    }

    public static function sameHolder(Position $position): self
    {
        return new self("Position [{$position->code}] is already assigned to that user.");
    }

    public static function alreadyEnded(PositionAssignment $assignment): self
    {
        return new self("Position assignment [{$assignment->getKey()}] has already ended.");
    }

    public static function invalidEffectiveTime(): self
    {
        return new self('The server effective time must be after the assignment start time.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
