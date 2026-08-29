<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class OrganizationConflict extends RuntimeException
{
    public static function activeDependencies(string $resource, string $dependencies): self
    {
        return new self("{$resource} tidak dapat dinonaktifkan karena masih memiliki {$dependencies} aktif.");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_CONFLICT);
    }
}
