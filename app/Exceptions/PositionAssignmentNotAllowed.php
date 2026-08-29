<?php

namespace App\Exceptions;

use App\Models\Position;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PositionAssignmentNotAllowed extends RuntimeException
{
    public static function ineligibleActor(): self
    {
        return new self('Position assignments require an active, verified internal actor.');
    }

    public static function ineligibleAssignee(): self
    {
        return new self('Only active internal users may receive a Position assignment.');
    }

    public static function inactivePosition(Position $position): self
    {
        return new self("Inactive Position [{$position->code}] cannot receive a new assignment.");
    }

    public static function inactivePositionDependency(Position $position): self
    {
        return new self("Position [{$position->code}] memiliki level atau unit organisasi yang tidak aktif.");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
