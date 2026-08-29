<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class OrganizationNotAllowed extends RuntimeException
{
    public static function inactiveParent(): self
    {
        return new self('Unit induk harus aktif.');
    }

    public static function hierarchyCycle(): self
    {
        return new self('Unit induk tidak boleh membentuk siklus hierarki.');
    }

    public static function inactivePositionDependency(): self
    {
        return new self('Level jabatan dan unit organisasi yang dipilih harus aktif.');
    }

    public static function unprotectedPositionLevel(): self
    {
        return new self('Jabatan hanya dapat menggunakan level resmi dari katalog terlindungi.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
